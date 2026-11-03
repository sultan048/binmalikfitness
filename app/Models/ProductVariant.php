<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'cost_price', // staff-only
    ];

    protected function casts(): array
    {
        return [
            'sample_expiry_date' => 'date',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class)->orderBy('expires_at');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('created_at');
    }

    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function availableStock(): int
    {
        return max(0, $this->stock_on_hand - $this->stock_reserved);
    }

    public function isLowStock(): bool
    {
        return $this->availableStock() <= $this->low_stock_threshold;
    }

    public function label(): string
    {
        return trim(collect([$this->size, $this->flavor])->filter()->implode(' · ')) ?: $this->sku;
    }
}
