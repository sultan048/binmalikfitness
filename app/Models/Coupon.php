<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValidForSubtotal(int $subtotalPaisa): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($subtotalPaisa < $this->min_subtotal_paisa) {
            return false;
        }

        return true;
    }

    public function calculateDiscountPaisa(int $subtotalPaisa): int
    {
        if (! $this->isValidForSubtotal($subtotalPaisa)) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            $discount = (int) round($subtotalPaisa * ($this->discount_value / 100));
        } else {
            $discount = (int) $this->discount_value;
        }

        if ($this->max_discount_paisa !== null) {
            $discount = min($discount, (int) $this->max_discount_paisa);
        }

        return min($discount, $subtotalPaisa);
    }
}
