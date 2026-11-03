<?php

namespace App\Domain\Commerce;

use App\Domain\Inventory\InventoryService;
use App\Domain\Platform\AuditLogger;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\InventoryBatch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckoutService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Validates the cart, reserves stock atomically, snapshots prices & batches,
     * and creates the Order and initial Payment inside a single database transaction.
     */
    public function placeOrder(Cart $cart, array $data, ?User $user = null): Order
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;

        if ($idempotencyKey && Order::where('idempotency_key', $idempotencyKey)->exists()) {
            throw new HttpException(409, 'Duplicate checkout submission detected. Your order has already been placed.');
        }

        $cart->load(['items.product', 'items.variant', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart is empty. Please add at least one product before checking out.'],
            ]);
        }

        $paymentMethod = $data['payment_method'] ?? 'cod';
        if ($paymentMethod === 'online_gateway') {
            throw ValidationException::withMessages([
                'payment_method' => ['Online card/wallet payment gateway is disabled until provider & webhook capabilities are confirmed. Please choose Cash on Delivery or Manual Bank Transfer.'],
            ]);
        }

        return DB::transaction(function () use ($cart, $data, $user, $idempotencyKey, $paymentMethod) {
            $shippingMethod = $data['shipping_method'] ?? ($cart->shipping_method ?: 'standard_courier');
            $totals = $this->pricing->calculateCartTotals($cart, $shippingMethod);

            if ($totals['subtotal_paisa'] <= 0 || empty($totals['lines'])) {
                throw ValidationException::withMessages(['cart' => ['Unable to compute valid order lines.']]);
            }

            // Re-validate coupon under lock if applied
            if ($cart->coupon_id) {
                $coupon = Coupon::query()->lockForUpdate()->find($cart->coupon_id);
                if (! $coupon || ! $coupon->isValidForSubtotal($totals['subtotal_paisa'])) {
                    throw ValidationException::withMessages([
                        'coupon_code' => ['The applied coupon is no longer valid for this order.'],
                    ]);
                }
                $coupon->increment('used_count');
            }

            // Resolve shipping address snapshot (either from saved address_id or submitted fields)
            $addressSnapshot = $this->resolveAddressSnapshot($data, $user);

            $orderStatus = $paymentMethod === 'cod'
                ? Order::STATUS_COD_CONFIRMED
                : Order::STATUS_AWAITING_PAYMENT;

            $paymentStatus = $paymentMethod === 'cod'
                ? Order::PAYMENT_COD_PENDING
                : Order::PAYMENT_UNPAID;

            $orderNumber = 'BMF-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

            $order = Order::create([
                'order_number' => $orderNumber,
                'access_token' => Str::random(48),
                'idempotency_key' => $idempotencyKey,
                'user_id' => $user?->id,
                'assigned_manager_id' => null,
                'customer_name' => $data['customer_name'] ?? $addressSnapshot['recipient_name'],
                'customer_email' => strtolower(trim($data['customer_email'])),
                'customer_phone' => $data['customer_phone'] ?? $addressSnapshot['phone'],
                'shipping_address_snapshot' => $addressSnapshot,
                'shipping_method' => $shippingMethod,
                'payment_method' => $paymentMethod,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'currency' => 'PKR',
                'subtotal_paisa' => $totals['subtotal_paisa'],
                'discount_paisa' => $totals['discount_paisa'],
                'shipping_paisa' => $totals['shipping_paisa'],
                'tax_paisa' => $totals['tax_paisa'],
                'total_paisa' => $totals['total_paisa'],
                'refunded_paisa' => 0,
                'coupon_code_snapshot' => $totals['coupon_code_snapshot'],
                'customer_notes' => $data['customer_notes'] ?? null,
                'placed_at' => now(),
                'confirmed_at' => $paymentMethod === 'cod' ? now() : null,
                'is_demo' => (bool) ($user?->is_demo ?? false),
            ]);

            foreach ($totals['lines'] as $line) {
                $variant = ProductVariant::query()->findOrFail($line['product_variant_id']);

                // Atomically reserve stock (throws ValidationException and rolls back transaction on oversell)
                $this->inventory->reserve(
                    $variant,
                    (int) $line['quantity'],
                    'order',
                    $order->id,
                    $user
                );

                $earliestBatch = InventoryBatch::query()
                    ->where('product_variant_id', $variant->id)
                    ->where('quantity_remaining', '>', 0)
                    ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END, expires_at ASC, id ASC')
                    ->first();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $variant->id,
                    'product_name_snapshot' => $line['product_name'],
                    'sku_snapshot' => $line['sku'],
                    'variant_label_snapshot' => $line['variant_label'],
                    'batch_number_snapshot' => $earliestBatch?->batch_number ?? $variant->sample_lot_number,
                    'expiry_date_snapshot' => $earliestBatch?->expires_at?->toDateString() ?? $variant->sample_expiry_date?->toDateString(),
                    'quantity' => (int) $line['quantity'],
                    'unit_price_paisa' => (int) $line['unit_price_paisa'],
                    'line_total_paisa' => (int) $line['line_total_paisa'],
                    'weight_grams_snapshot' => (int) $line['weight_grams'],
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'status' => 'pending',
                'amount_paisa' => $order->total_paisa,
                'currency' => 'PKR',
            ]);

            // Optionally save address for authenticated customer
            if ($user && empty($data['saved_address_id']) && ! empty($data['save_address'])) {
                Address::create([
                    'user_id' => $user->id,
                    'label' => $data['address_label'] ?? 'Delivery Address',
                    'recipient_name' => $addressSnapshot['recipient_name'],
                    'phone' => $addressSnapshot['phone'],
                    'line1' => $addressSnapshot['line1'],
                    'area' => $addressSnapshot['area'] ?? null,
                    'city' => $addressSnapshot['city'],
                    'province' => $addressSnapshot['province'],
                    'postal_code' => $addressSnapshot['postal_code'] ?? null,
                    'is_default' => ! $user->addresses()->exists(),
                ]);
            }

            // Clear cart
            $cart->items()->delete();
            $cart->update(['coupon_id' => null]);

            $this->audit->record(
                'commerce.order.placed',
                $order,
                null,
                [
                    'order_number' => $order->order_number,
                    'payment_method' => $order->payment_method,
                    'total_paisa' => $order->total_paisa,
                    'status' => $order->status,
                ],
                $user
            );

            return $order->load(['items', 'payments']);
        });
    }

    private function resolveAddressSnapshot(array $data, ?User $user): array
    {
        if ($user && ! empty($data['saved_address_id'])) {
            $saved = Address::query()
                ->where('user_id', $user->id)
                ->findOrFail((int) $data['saved_address_id']);

            return [
                'recipient_name' => $saved->recipient_name,
                'phone' => $saved->phone,
                'line1' => $saved->line1,
                'area' => $saved->area,
                'city' => $saved->city,
                'province' => $saved->province,
                'postal_code' => $saved->postal_code,
            ];
        }

        return [
            'recipient_name' => $data['customer_name'],
            'phone' => $data['customer_phone'],
            'line1' => $data['line1'],
            'area' => $data['area'] ?? null,
            'city' => $data['city'],
            'province' => $data['province'],
            'postal_code' => $data['postal_code'] ?? null,
        ];
    }
}
