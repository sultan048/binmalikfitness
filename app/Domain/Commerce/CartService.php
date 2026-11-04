<?php

namespace App\Domain\Commerce;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function resolveCart(Request $request, bool $create = false): ?Cart
    {
        $user = $request->user();
        $sessionToken = (string) $request->session()->get('bmf_cart_token', '');
        if ($sessionToken === '') {
            $sessionToken = Str::uuid()->toString();
            $request->session()->put('bmf_cart_token', $sessionToken);
        }

        if ($user) {
            $userCart = Cart::query()->where('user_id', $user->id)->first();
            $guestCart = Cart::query()->whereNull('user_id')->where('session_token', $sessionToken)->first();

            if ($guestCart && $userCart && $guestCart->id !== $userCart->id) {
                foreach ($guestCart->items as $gItem) {
                    $existing = $userCart->items()->where('product_variant_id', $gItem->product_variant_id)->first();
                    if ($existing) {
                        $existing->update(['quantity' => $existing->quantity + $gItem->quantity]);
                    } else {
                        $userCart->items()->create([
                            'product_id' => $gItem->product_id,
                            'product_variant_id' => $gItem->product_variant_id,
                            'quantity' => $gItem->quantity,
                        ]);
                    }
                }
                if (! $userCart->coupon_id && $guestCart->coupon_id) {
                    $userCart->update(['coupon_id' => $guestCart->coupon_id]);
                }
                $guestCart->delete();
            } elseif ($guestCart && ! $userCart) {
                $guestCart->update(['user_id' => $user->id]);
                $userCart = $guestCart;
            }

            if (! $userCart && $create) {
                $userCart = Cart::create([
                    'user_id' => $user->id,
                    'session_token' => $sessionToken,
                    'shipping_method' => 'standard_courier',
                ]);
            }

            return $userCart;
        }

        $cart = Cart::query()->whereNull('user_id')->where('session_token', $sessionToken)->first();

        if (! $cart && $create) {
            $cart = Cart::create([
                'user_id' => null,
                'session_token' => $sessionToken,
                'shipping_method' => 'standard_courier',
            ]);
        }

        return $cart;
    }

    public function addItem(Cart $cart, int $variantId, int $quantity = 1): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => ['Quantity must be at least 1.']]);
        }

        $variant = ProductVariant::with('product')->findOrFail($variantId);

        if (! $variant->is_active || ! $variant->product || $variant->product->status !== 'active') {
            throw ValidationException::withMessages(['variant_id' => ['This product variant is currently unavailable.']]);
        }

        $existing = $cart->items()->where('product_variant_id', $variant->id)->first();
        $newQuantity = ($existing ? $existing->quantity : 0) + $quantity;

        if ($newQuantity > $variant->availableStock()) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$variant->availableStock()} unit(s) of {$variant->sku} are currently available."],
            ]);
        }

        if ($existing) {
            $existing->update(['quantity' => $newQuantity]);

            return $existing->fresh();
        }

        return $cart->items()->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    public function updateItemQuantity(Cart $cart, int $cartItemId, int $quantity): void
    {
        $item = $cart->items()->with('variant')->findOrFail($cartItemId);

        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        if ($quantity > $item->variant->availableStock()) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$item->variant->availableStock()} unit(s) of {$item->variant->sku} are available."],
            ]);
        }

        $item->update(['quantity' => $quantity]);
    }

    public function removeItem(Cart $cart, int $cartItemId): void
    {
        $cart->items()->where('id', $cartItemId)->delete();
    }

    public function applyCoupon(Cart $cart, string $rawCode, ?User $user = null): Coupon
    {
        $code = strtoupper(trim($rawCode));
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active) {
            throw ValidationException::withMessages(['coupon_code' => ['Invalid or inactive coupon code.']]);
        }

        $subtotalPaisa = $cart->items()->with('variant')->get()->sum(
            fn (CartItem $item) => ((int) $item->variant?->price) * $item->quantity
        );

        if (! $coupon->isValidForSubtotal($subtotalPaisa)) {
            throw ValidationException::withMessages([
                'coupon_code' => ['This coupon does not meet minimum order requirements or has expired.'],
            ]);
        }

        if ($user && $coupon->per_user_limit > 0) {
            $usedByUser = Order::query()
                ->where('user_id', $user->id)
                ->where('coupon_code_snapshot', $coupon->code)
                ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_FAILED])
                ->count();

            if ($usedByUser >= $coupon->per_user_limit) {
                throw ValidationException::withMessages([
                    'coupon_code' => ['You have already used this coupon the maximum number of times.'],
                ]);
            }
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return $coupon;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->update(['coupon_id' => null]);
    }
}
