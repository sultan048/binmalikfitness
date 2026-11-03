<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoachingPackage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'includes_custom_diet' => 'boolean',
            'includes_custom_workout' => 'boolean',
            'includes_video_library' => 'boolean',
            'includes_consultation' => 'boolean',
            'inclusions' => 'array',
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CoachingSubscription::class);
    }
}
