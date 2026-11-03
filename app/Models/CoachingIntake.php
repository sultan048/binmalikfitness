<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachingIntake extends Model
{
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_FLAGGED_MEDICAL = 'flagged_medical';
    public const STATUS_CLEARED = 'cleared';
    public const STATUS_PLAN_DELIVERED = 'plan_delivered';

    protected $guarded = ['id'];

    protected $hidden = [
        'health_notes_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'float',
            'health_consent_granted' => 'boolean',
            'health_notes_encrypted' => 'encrypted',
            'needs_professional_review' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CoachingSubscription::class, 'coaching_subscription_id');
    }

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class);
    }

    public function consentRecord(): BelongsTo
    {
        return $this->belongsTo(ConsentRecord::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
