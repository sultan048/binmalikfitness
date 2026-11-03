<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutProgram extends Model
{
    public const CATEGORIES = [
        'Beginner Fitness',
        'Muscle Building',
        'Strength Training',
        'Home Workouts',
        'Fat Loss Training',
        'Advanced Training',
        'Mobility and Recovery',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'structure' => 'array',
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class);
    }
}
