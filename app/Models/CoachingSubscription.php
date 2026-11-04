<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoachingSubscription extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVOKED = 'revoked';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'paused_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'renewal_reminder_sent_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function isCurrentlyActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isExpiringSoon(int $withinDays = 7): bool
    {
        return $this->isCurrentlyActive()
            && $this->ends_at !== null
            && $this->ends_at->diffInDays(now(), false) >= -$withinDays
            && $this->ends_at->isFuture();
    }

    public function daysRemaining(): int
    {
        if (! $this->ends_at || $this->ends_at->isPast()) {
            return 0;
        }

        return (int) max(0, ceil(now()->floatDiffInDays($this->ends_at, false)));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CoachingPackage::class, 'coaching_package_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function latestOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'latest_order_id');
    }

    public function intake(): HasOne
    {
        return $this->hasOne(CoachingIntake::class)->latestOfMany();
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CoachingCheckIn::class)->orderByDesc('check_in_date');
    }

    public function workoutAssignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(CoachingConsultation::class)->orderBy('scheduled_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CoachingMessage::class)->orderBy('created_at');
    }
}
