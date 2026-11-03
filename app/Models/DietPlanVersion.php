<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietPlanVersion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'hydration_target_liters' => 'float',
            'meal_schedule' => 'array',
            'substitutions' => 'array',
            'shopping_list' => 'array',
            'published_at' => 'datetime',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
