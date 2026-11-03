<?php

namespace App\Domain\Payments\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;

interface PaymentGatewayInterface
{
    /**
     * Initiates an external gateway payment session once a supported Pakistan gateway is configured.
     */
    public function initiatePayment(Order $order): array;

    /**
     * Idempotently processes a signed gateway webhook event.
     */
    public function handleWebhook(string $eventId, string $eventType, array $payload): PaymentWebhookLog;

    /**
     * Initiates a refund through the gateway if applicable.
     */
    public function refundPayment(Payment $payment, int $amountPaisa, string $reason): string;
}
