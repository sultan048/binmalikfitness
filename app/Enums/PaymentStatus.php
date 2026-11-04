<?php

namespace App\Enums;

/**
 * Order payment states. The orders.payment_status column stores ->value;
 * Order::PAYMENT_* constants mirror these values.
 */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case CodPending = 'cod_pending';
    case ProofSubmitted = 'proof_submitted';
    case Verified = 'verified';
    case Failed = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
