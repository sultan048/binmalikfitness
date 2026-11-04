<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Customer e-mail for order milestones. Queued (sent by the scheduled queue worker) and
 * dispatched only after the surrounding database transaction commits.
 */
class OrderUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const EVENT_PLACED = 'placed';

    public const EVENT_PAYMENT_VERIFIED = 'payment_verified';

    public const EVENT_SHIPPED = 'shipped';

    public const EVENT_CANCELLED = 'cancelled';

    public int $tries = 3;

    public function __construct(
        public readonly Order $order,
        public readonly string $event,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $number = $order->order_number;
        $total = Money::format((int) $order->total_paisa);

        $message = (new MailMessage)
            ->greeting('Assalam o Alaikum '.($order->customer_name ?: 'there').',');

        $message = match ($this->event) {
            self::EVENT_PLACED => $message
                ->subject("Order {$number} received — BIN MALIK FITNESS")
                ->line("Thank you for your order. We have received order **{$number}** for **{$total}**.")
                ->line($order->payment_method === 'bank_transfer'
                    ? 'Please complete the bank transfer and upload your receipt from the order page. We start processing after our team verifies the payment.'
                    : 'Your Cash on Delivery order is confirmed. Please keep the exact amount ready for the courier.'),
            self::EVENT_PAYMENT_VERIFIED => $message
                ->subject("Payment verified for order {$number}")
                ->line("Our team has verified your payment of **{$total}** for order **{$number}**. Your order is now being processed."),
            self::EVENT_SHIPPED => $message
                ->subject("Order {$number} has shipped")
                ->line("Good news — order **{$number}** is on its way.")
                ->when($order->courier_name || $order->tracking_number, fn (MailMessage $m) => $m->line(
                    'Courier: '.($order->courier_name ?: '—').' · Tracking number: '.($order->tracking_number ?: '—')
                )),
            self::EVENT_CANCELLED => $message
                ->subject("Order {$number} cancelled")
                ->line("Order **{$number}** has been cancelled.")
                ->when($order->cancellation_reason, fn (MailMessage $m) => $m->line('Reason: '.$order->cancellation_reason)),
            default => $message->subject("Update on order {$number}")->line("There is an update on order **{$number}**."),
        };

        return $message
            ->action('Track your order', route('orders.track', ['order_number' => $number]))
            ->line('Questions? Reply to our WhatsApp support or contact us through your account.')
            ->salutation('— BIN MALIK FITNESS');
    }
}
