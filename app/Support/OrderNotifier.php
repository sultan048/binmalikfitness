<?php

namespace App\Support;

use App\Models\Order;
use App\Notifications\OrderUpdateNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends order milestone e-mails to the address on the order (guests included).
 * Demo orders are skipped so seeded data never e-mails real mailboxes.
 */
final class OrderNotifier
{
    public static function send(Order $order, string $event): void
    {
        if ($order->is_demo || ! filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Notification::route('mail', $order->customer_email)
                ->notify(new OrderUpdateNotification($order, $event));
        } catch (Throwable $e) {
            // A mail problem must never break checkout or fulfilment
            report($e);
        }
    }
}
