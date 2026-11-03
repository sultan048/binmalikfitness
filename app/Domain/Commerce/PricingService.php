<?php

namespace App\Domain\Commerce;

use App\Models\Cart;
use App\Models\Coupon;
use App\Support\Money;

class PricingService
{
    public const FREE_SHIPPING_THRESHOLD_PAISA = 1000000; // PKR 10,000
    public const STANDARD_SHIPPING_PAISA = 35000;        // PKR 350
    public const EXPRESS_SHIPPING_PAISA = 65000;         // PKR 650

    /**
     * Recomputes all cart line prices, coupon discounts, shipping, and totals on the server.
     * Client-submitted prices or totals are never trusted.
     */
    public function calculateCartTotals(Cart $cart, ?string $shippingMethodOverride = null): array
    {
        $cart->loadMissing(['items.product', 'items.variant', 'coupon']);

        $shippingMethod = $shippingMethodOverride ?: ($cart->shipping_method ?: 'standard_courier');
        $subtotalPaisa = 0;
        $totalWeightGrams = 0;
        $lines = [];

        foreach ($cart->items as $item) {
            if (! $item->variant || ! $item->product) {
                continue;
            }

            $unitPricePaisa = (int) $item->variant->price;
            $qty = (int) $item->quantity;
            $lineTotalPaisa = $unitPricePaisa * $qty;
            $subtotalPaisa += $lineTotalPaisa;
            $totalWeightGrams += ((int) $item->variant->weight_grams) * $qty;

            $lines[] = [
                'cart_item_id' => $item->id,
                'product_id' => $item->product->id,
                'product_variant_id' => $item->variant->id,
                'product_name' => $item->product->name,
                'product_slug' => $item->product->slug,
                'sku' => $item->variant->sku,
                'variant_label' => $item->variant->label(),
                'quantity' => $qty,
                'available_stock' => $item->variant->availableStock(),
                'unit_price_paisa' => $unitPricePaisa,
                'unit_price_formatted' => Money::format($unitPricePaisa),
                'line_total_paisa' => $lineTotalPaisa,
                'line_total_formatted' => Money::format($lineTotalPaisa),
                'weight_grams' => (int) $item->variant->weight_grams,
                'sample_lot_number' => $item->variant->sample_lot_number,
                'sample_expiry_date' => $item->variant->sample_expiry_date?->toDateString(),
            ];
        }

        $discountPaisa = 0;
        $couponCodeSnapshot = null;

        /** @var Coupon|null $coupon */
        $coupon = $cart->coupon;
        if ($coupon && $subtotalPaisa > 0 && $coupon->isValidForSubtotal($subtotalPaisa)) {
            $discountPaisa = $coupon->calculateDiscountPaisa($subtotalPaisa);
            $couponCodeSnapshot = $coupon->code;
        }

        $netAfterDiscount = max(0, $subtotalPaisa - $discountPaisa);

        $shippingPaisa = 0;
        if ($subtotalPaisa > 0) {
            if ($shippingMethod === 'express_city') {
                $shippingPaisa = self::EXPRESS_SHIPPING_PAISA;
            } else {
                $shippingPaisa = $netAfterDiscount >= self::FREE_SHIPPING_THRESHOLD_PAISA
                    ? 0
                    : self::STANDARD_SHIPPING_PAISA;
            }
        }

        $taxPaisa = 0; // Inclusive GST in listed retail PKR prices
        $totalPaisa = $netAfterDiscount + $shippingPaisa + $taxPaisa;

        return [
            'currency' => 'PKR',
            'shipping_method' => $shippingMethod,
            'subtotal_paisa' => $subtotalPaisa,
            'subtotal_formatted' => Money::format($subtotalPaisa),
            'discount_paisa' => $discountPaisa,
            'discount_formatted' => Money::format($discountPaisa),
            'shipping_paisa' => $shippingPaisa,
            'shipping_formatted' => $shippingPaisa === 0 && $subtotalPaisa > 0 ? 'FREE' : Money::format($shippingPaisa),
            'tax_paisa' => $taxPaisa,
            'tax_formatted' => Money::format($taxPaisa),
            'total_paisa' => $totalPaisa,
            'total_formatted' => Money::format($totalPaisa),
            'coupon_code_snapshot' => $couponCodeSnapshot,
            'free_shipping_threshold_paisa' => self::FREE_SHIPPING_THRESHOLD_PAISA,
            'amount_to_free_shipping_paisa' => max(0, self::FREE_SHIPPING_THRESHOLD_PAISA - $netAfterDiscount),
            'total_weight_grams' => $totalWeightGrams,
            'item_count' => collect($lines)->sum('quantity'),
            'lines' => $lines,
        ];
    }
}
