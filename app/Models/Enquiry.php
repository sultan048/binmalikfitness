<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'preferred_trial_date' => 'date',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function interestedPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'interested_membership_plan_id');
    }
}
