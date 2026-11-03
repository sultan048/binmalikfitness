<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DietPlan extends Model
{
    public const CATEGORIES = [
        'Muscle Gain / Bulking',
        'Fat Loss / Weight Loss',
        'Body Recomposition',
        'Lean Muscle',
        'General Fitness Nutrition',
        'Maintenance',
        'Customized Diet Plans',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sample_day' => 'array',
            'inclusions' => 'array',
            'dietary_tags' => 'array',
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DietPlanVersion::class)->orderByDesc('version_number');
    }

    public function templateVersion(): HasOne
    {
        return $this->hasOne(DietPlanVersion::class)
            ->whereNull('user_id')
            ->where('is_active', true)
            ->latestOfMany('version_number');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(PersonalisedPlanRequest::class);
    }
}
