<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalisedPlanRequest extends Model
{
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_AWAITING_INTAKE = 'awaiting_intake';

    public const STATUS_IN_PREPARATION = 'in_preparation';

    public const STATUS_MEDICAL_HOLD = 'medical_hold';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_REVISION_REQUESTED = 'revision_requested';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'custom_substitution_overrides' => 'array',
            'delivered_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(CoachingIntake::class, 'coaching_intake_id');
    }

    public function assignedCoach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_coach_id');
    }

    public function deliveredVersion(): BelongsTo
    {
        return $this->belongsTo(DietPlanVersion::class, 'delivered_version_id');
    }
}
