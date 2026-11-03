<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price' => 'integer',
            'registration_fee' => 'integer',
            'inclusions' => 'array',
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GymMembership::class);
    }
}
