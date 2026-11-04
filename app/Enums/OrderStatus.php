<?php

namespace App\Enums;

/**
 * Order lifecycle states and the staff-driven transitions between them.
 * The orders.status column stores ->value; Order::STATUS_* constants mirror these values.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case CodConfirmed = 'cod_confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
    case Refunded = 'refunded';
    case Failed = 'failed';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::AwaitingPayment, self::CodConfirmed, self::Processing, self::Cancelled, self::Failed],
            self::AwaitingPayment => [self::Processing, self::Cancelled, self::Failed],
            self::CodConfirmed => [self::Processing, self::Shipped, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered => [self::Returned, self::Refunded],
            self::Returned => [self::Refunded],
            self::Cancelled, self::Refunded, self::Failed => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
