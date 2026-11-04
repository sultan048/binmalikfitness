<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use RuntimeException;

class Order extends Model
{
    // String constants kept for existing code and views; the enums are the source of truth.
    public const STATUS_PENDING = OrderStatus::Pending->value;

    public const STATUS_AWAITING_PAYMENT = OrderStatus::AwaitingPayment->value;

    public const STATUS_COD_CONFIRMED = OrderStatus::CodConfirmed->value;

    public const STATUS_PROCESSING = OrderStatus::Processing->value;

    public const STATUS_SHIPPED = OrderStatus::Shipped->value;

    public const STATUS_DELIVERED = OrderStatus::Delivered->value;

    public const STATUS_CANCELLED = OrderStatus::Cancelled->value;

    public const STATUS_RETURNED = OrderStatus::Returned->value;

    public const STATUS_REFUNDED = OrderStatus::Refunded->value;

    public const STATUS_FAILED = OrderStatus::Failed->value;

    public const PAYMENT_UNPAID = PaymentStatus::Unpaid->value;

    public const PAYMENT_COD_PENDING = PaymentStatus::CodPending->value;

    public const PAYMENT_PROOF_SUBMITTED = PaymentStatus::ProofSubmitted->value;

    public const PAYMENT_VERIFIED = PaymentStatus::Verified->value;

    public const PAYMENT_FAILED = PaymentStatus::Failed->value;

    public const PAYMENT_PARTIALLY_REFUNDED = PaymentStatus::PartiallyRefunded->value;

    public const PAYMENT_REFUNDED = PaymentStatus::Refunded->value;

    protected $guarded = ['id'];

    /**
     * Generates a unique, hard-to-guess order number, retrying on the (rare) collision.
     * The orders.order_number unique index remains the final guarantee.
     */
    public static function generateNumber(string $prefix = 'BMF'): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(8));
            if (! static::query()->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Could not generate a unique order number.');
    }

    public function statusEnum(): ?OrderStatus
    {
        return OrderStatus::tryFrom((string) $this->status);
    }

    public function paymentStatusEnum(): ?PaymentStatus
    {
        return PaymentStatus::tryFrom((string) $this->payment_status);
    }

    protected function casts(): array
    {
        return [
            'shipping_address_snapshot' => 'array',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_manager_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->latest();
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(OrderReturnRequest::class)->latest();
    }

    public function canCustomerCancel(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_AWAITING_PAYMENT,
            self::STATUS_COD_CONFIRMED,
        ], true);
    }

    public function canRequestReturn(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }
}
