<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use RuntimeException;

class NullPaymentGateway implements PaymentGatewayInterface
{
    public function initiatePayment(Order $order): array
    {
        throw new RuntimeException(
            'Online payment gateway is disabled until the payment provider and webhook capabilities are confirmed by the owner.'
        );
    }

    public function handleWebhook(string $eventId, string $eventType, array $payload): PaymentWebhookLog
    {
        $existing = PaymentWebhookLog::where('event_id', $eventId)->first();
        if ($existing) {
            return $existing;
        }

        return PaymentWebhookLog::create([
            'provider' => 'unconfigured_gateway',
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => $payload,
            'processed_at' => now(),
            'status' => 'ignored_gateway_disabled',
        ]);
    }

    public function refundPayment(Payment $payment, int $amountPaisa, string $reason): string
    {
        return 'MANUAL-REFUND-'.now()->format('YmdHis');
    }
}
