<?php

namespace App\Http\Controllers\Public;

use App\Domain\Commerce\CartService;
use App\Domain\Commerce\CheckoutService;
use App\Domain\Commerce\OrderFulfilmentService;
use App\Domain\Commerce\PricingService;
use App\Domain\Payments\PaymentService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\StockAlert;
use App\Models\WishlistItem;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommerceController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly PricingService $pricing,
        private readonly CheckoutService $checkout,
        private readonly PaymentService $payments,
        private readonly OrderFulfilmentService $fulfilment,
    ) {}

    public function shop(Request $request): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('sort_order')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        $query = Product::with(['category', 'brand', 'defaultVariant', 'variants', 'primaryImage'])
            ->where('status', 'active');

        if ($request->filled('category')) {
            $categorySlug = (string) $request->query('category');
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($request->filled('brand')) {
            $brandSlug = (string) $request->query('brand');
            $query->whereHas('brand', fn ($q) => $q->where('slug', $brandSlug));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('subtitle', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%");
            });
        }

        if ($request->filled('min_price')) {
            $minPaisa = Money::fromRupees((float) $request->query('min_price'));
            $query->whereHas('variants', fn ($q) => $q->where('price', '>=', $minPaisa));
        }

        if ($request->filled('max_price')) {
            $maxPaisa = Money::fromRupees((float) $request->query('max_price'));
            $query->whereHas('variants', fn ($q) => $q->where('price', '<=', $maxPaisa));
        }

        if ($request->boolean('in_stock')) {
            $query->whereHas('variants', fn ($q) => $q->whereRaw('(stock_on_hand - stock_reserved) > 0'));
        }

        if ($request->query('collection') === 'bestsellers') {
            $query->where('is_bestseller', true);
        } elseif ($request->query('collection') === 'featured') {
            $query->where('is_featured', true);
        }

        $sort = (string) $request->query('sort', 'featured');
        if ($sort === 'price_asc') {
            $query->orderBy(
                ProductVariant::select('price')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_default', true)
                    ->limit(1),
                'asc'
            );
        } elseif ($sort === 'price_desc') {
            $query->orderBy(
                ProductVariant::select('price')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_default', true)
                    ->limit(1),
                'desc'
            );
        } elseif ($sort === 'newest') {
            $query->latest('id');
        } else {
            $query->orderByDesc('is_bestseller')->orderByDesc('is_featured')->orderBy('id');
        }

        $products = $query->get();

        $wishlistedIds = $this->resolveWishlistProductIds($request);
        $recentlyViewedIds = (array) $request->session()->get('recently_viewed_product_ids', []);
        $recentlyViewed = ! empty($recentlyViewedIds)
            ? Product::with(['defaultVariant', 'primaryImage'])->whereIn('id', $recentlyViewedIds)->where('status', 'active')->get()
            : collect();

        $cart = $this->carts->resolveCart($request, false);
        $cartTotals = $cart ? $this->pricing->calculateCartTotals($cart) : null;

        return view('public.shop', compact(
            'categories',
            'brands',
            'products',
            'wishlistedIds',
            'recentlyViewed',
            'cartTotals',
            'sort'
        ));
    }

    public function productShow(Request $request, string $slug): View
    {
        $product = Product::with([
            'category',
            'brand',
            'variants.batches',
            'defaultVariant',
            'images',
            'primaryImage',
            'documents',
            'approvedReviews.user',
        ])->where('slug', $slug)->firstOrFail();

        // Track recently viewed in session
        $recent = collect((array) $request->session()->get('recently_viewed_product_ids', []))
            ->reject(fn ($id) => (int) $id === $product->id)
            ->prepend($product->id)
            ->take(6)
            ->values()
            ->all();
        $request->session()->put('recently_viewed_product_ids', $recent);

        $relatedProducts = Product::with(['category', 'defaultVariant', 'primaryImage'])
            ->where('status', 'active')
            ->where('id', '!=', $product->id)
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$product->category_id])
            ->take(3)
            ->get();

        $recentlyViewed = Product::with(['category', 'defaultVariant', 'primaryImage'])
            ->whereIn('id', array_diff($recent, [$product->id]))
            ->where('status', 'active')
            ->take(3)
            ->get();

        $wishlistedIds = $this->resolveWishlistProductIds($request);

        $eligibleOrderForReview = null;
        if ($request->user()) {
            $eligibleOrderForReview = Order::query()
                ->where('user_id', $request->user()->id)
                ->where('status', Order::STATUS_DELIVERED)
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->whereDoesntHave('user.roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
                ->whereNotExists(function ($sub) use ($product, $request) {
                    $sub->selectRaw('1')
                        ->from('product_reviews')
                        ->whereColumn('product_reviews.order_id', 'orders.id')
                        ->where('product_reviews.product_id', $product->id)
                        ->where('product_reviews.user_id', $request->user()->id);
                })
                ->first();
        }

        return view('public.product-show', compact(
            'product',
            'relatedProducts',
            'recentlyViewed',
            'wishlistedIds',
            'eligibleOrderForReview'
        ));
    }

    public function cartCheckout(Request $request): View
    {
        $cart = $this->carts->resolveCart($request, true);
        $shippingMethod = (string) $request->query('shipping_method', $cart->shipping_method ?: 'standard_courier');
        if ($shippingMethod !== $cart->shipping_method && in_array($shippingMethod, ['standard_courier', 'express_city'], true)) {
            $cart->update(['shipping_method' => $shippingMethod]);
        }

        $totals = $this->pricing->calculateCartTotals($cart, $cart->shipping_method);
        $savedAddresses = $request->user()
            ? $request->user()->addresses()->orderByDesc('is_default')->get()
            : collect();

        $bankSettings = Setting::whereIn('key', [
            'payment.bank_name',
            'payment.account_title',
            'payment.iban',
        ])->pluck('value', 'key');

        return view('public.cart-checkout', compact('cart', 'totals', 'savedAddresses', 'bankSettings'));
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $cart = $this->carts->resolveCart($request, true);
        $item = $this->carts->addItem(
            $cart,
            (int) $validated['product_variant_id'],
            (int) ($validated['quantity'] ?? 1)
        );

        return redirect()->route('cart.index')
            ->with('status', "Added {$item->variant->sku} (×{$validated['quantity']}) to your cart.");
    }

    public function updateCartItem(Request $request, int $cartItem): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $cart = $this->carts->resolveCart($request, true);
        $this->carts->updateItemQuantity($cart, $cartItem, (int) $validated['quantity']);

        return back()->with('status', 'Cart quantity updated.');
    }

    public function removeCartItem(Request $request, int $cartItem): RedirectResponse
    {
        $cart = $this->carts->resolveCart($request, true);
        $this->carts->removeItem($cart, $cartItem);

        return back()->with('status', 'Item removed from cart.');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:40'],
        ]);

        $cart = $this->carts->resolveCart($request, true);
        $coupon = $this->carts->applyCoupon($cart, $validated['coupon_code'], $request->user());

        return back()->with('status', "Coupon {$coupon->code} applied to your cart.");
    }

    public function removeCoupon(Request $request): RedirectResponse
    {
        $cart = $this->carts->resolveCart($request, true);
        $this->carts->removeCoupon($cart);

        return back()->with('status', 'Coupon removed from cart.');
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $rules = [
            '_idempotency_key' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'string', 'email', 'max:190'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'saved_address_id' => ['nullable', 'integer'],
            'shipping_method' => ['required', 'string', 'in:standard_courier,express_city'],
            'payment_method' => ['required', 'string', 'in:cod,bank_transfer,online_gateway'],
            'customer_notes' => ['nullable', 'string', 'max:500'],
            'save_address' => ['nullable', 'boolean'],
        ];

        if (! $request->filled('saved_address_id')) {
            $rules['line1'] = ['required', 'string', 'max:190'];
            $rules['area'] = ['nullable', 'string', 'max:120'];
            $rules['city'] = ['required', 'string', 'max:80'];
            $rules['province'] = ['required', 'string', 'max:80'];
            $rules['postal_code'] = ['nullable', 'string', 'max:20'];
        }

        $validated = $request->validate($rules);
        $validated['idempotency_key'] = $validated['_idempotency_key'] ?? $request->header('Idempotency-Key');

        $cart = $this->carts->resolveCart($request, true);
        $order = $this->checkout->placeOrder($cart, $validated, $request->user());

        return redirect()->route('orders.confirmation', [
            'orderNumber' => $order->order_number,
            'token' => $order->access_token,
        ])->with('status', "Order {$order->order_number} placed. Please review your payment and delivery details below.");
    }

    public function orderConfirmation(Request $request, string $orderNumber): View
    {
        $order = $this->resolveAuthorizedOrder($request, $orderNumber);

        $bankSettings = Setting::whereIn('key', [
            'payment.bank_name',
            'payment.account_title',
            'payment.iban',
        ])->pluck('value', 'key');

        return view('public.order-confirmation', compact('order', 'bankSettings'));
    }

    public function orderInvoice(Request $request, string $orderNumber): View
    {
        $order = $this->resolveAuthorizedOrder($request, $orderNumber);

        $brandSettings = Setting::whereIn('key', [
            'brand.name',
            'gym.city',
            'gym.address',
            'gym.phone',
            'gym.email',
        ])->pluck('value', 'key');

        return view('public.order-invoice', compact('order', 'brandSettings'));
    }

    public function trackOrder(Request $request): View
    {
        $order = null;
        $lookupError = null;

        if ($request->filled('order_number')) {
            $orderNum = strtoupper(trim((string) $request->query('order_number')));
            $contact = strtolower(trim((string) $request->query('contact', '')));

            $candidate = Order::with(['items', 'payments', 'returnRequests'])
                ->where('order_number', $orderNum)
                ->first();

            if (! $candidate) {
                $lookupError = "No order found matching number {$orderNum}.";
            } elseif (
                ($request->user() && $request->user()->can('view', $candidate)) ||
                ($contact !== '' && (strtolower($candidate->customer_email) === $contact || $candidate->customer_phone === $contact))
            ) {
                $order = $candidate;
            } else {
                $lookupError = 'Order number and customer email/phone did not match our records.';
            }
        }

        return view('public.order-track', compact('order', 'lookupError'));
    }

    public function submitPaymentProof(Request $request, string $orderNumber): RedirectResponse
    {
        $order = $this->resolveAuthorizedOrder($request, $orderNumber);

        $validated = $request->validate([
            'transaction_reference' => ['required', 'string', 'max:120'],
            'sender_bank_name' => ['required', 'string', 'max:120'],
            'sender_account_last4' => ['nullable', 'string', 'max:16'],
            'receipt_file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $this->payments->submitBankTransferProof(
            $order,
            $validated,
            $request->file('receipt_file'),
            $request->user()
        );

        return back()->with(
            'status',
            'Bank transfer proof submitted and stored privately. Your order remains Awaiting Payment Verification until reviewed by our staff.'
        );
    }

    public function cancelOrder(Request $request, string $orderNumber): RedirectResponse
    {
        $order = $this->resolveAuthorizedOrder($request, $orderNumber);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->fulfilment->customerCancel($order, $validated['reason'], $request->user());

        return back()->with('status', "Order {$order->order_number} has been cancelled and reserved stock released.");
    }

    public function requestReturn(Request $request, string $orderNumber): RedirectResponse
    {
        $order = $this->resolveAuthorizedOrder($request, $orderNumber);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'details' => ['required', 'string', 'max:1000'],
        ]);

        $this->fulfilment->requestReturn($order, $validated, $request->user());

        return back()->with('status', 'Return request submitted for staff review.');
    }

    public function toggleWishlist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $user = $request->user();
        $sessionToken = $this->resolveSessionToken($request);

        $query = WishlistItem::query()->where('product_id', $validated['product_id']);
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id')->where('session_token', $sessionToken);
        }

        $existing = $query->first();
        if ($existing) {
            $existing->delete();

            return back()->with('status', 'Removed item from your wishlist.');
        }

        WishlistItem::create([
            'user_id' => $user?->id,
            'session_token' => $sessionToken,
            'product_id' => (int) $validated['product_id'],
            'product_variant_id' => ! empty($validated['product_variant_id']) ? (int) $validated['product_variant_id'] : null,
        ]);

        return back()->with('status', 'Saved item to your wishlist.');
    }

    public function subscribeStockAlert(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        StockAlert::updateOrCreate(
            [
                'product_variant_id' => (int) $validated['product_variant_id'],
                'email' => strtolower(trim($validated['email'])),
            ],
            [
                'user_id' => $request->user()?->id,
                'phone' => $validated['phone'] ?? null,
                'status' => 'subscribed',
                'notified_at' => null,
            ]
        );

        return back()->with('status', 'Stock alert registered. We will notify you as soon as a new batch is received.');
    }

    public function submitReview(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();

        $deliveredOrder = Order::query()
            ->where('user_id', $user->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->first();

        if (! $deliveredOrder) {
            abort(403, 'Only customers with a delivered purchase of this product may submit a verified review.');
        }

        if (ProductReview::where('product_id', $product->id)->where('user_id', $user->id)->where('order_id', $deliveredOrder->id)->exists()) {
            return back()->withErrors(['review' => 'You have already submitted a review for this purchase.']);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['required', 'string', 'max:140'],
            'body' => ['required', 'string', 'max:1500'],
        ]);

        ProductReview::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_id' => $deliveredOrder->id,
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_verified_purchase' => true,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Your verified-purchase review has been submitted and is pending moderation.');
    }

    private function resolveAuthorizedOrder(Request $request, string $orderNumber): Order
    {
        $order = Order::with(['items.product', 'items.variant', 'payments.verifier', 'refunds.processor', 'returnRequests'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $token = (string) ($request->query('token') ?? $request->input('token', ''));
        if ($token !== '' && hash_equals($order->access_token, $token)) {
            return $order;
        }

        if ($request->user() && $request->user()->can('view', $order)) {
            return $order;
        }

        abort(403, 'You are not authorized to access this order.');
    }

    private function resolveWishlistProductIds(Request $request): array
    {
        $user = $request->user();
        $sessionToken = $this->resolveSessionToken($request);

        return WishlistItem::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id), fn ($q) => $q->whereNull('user_id')->where('session_token', $sessionToken))
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function resolveSessionToken(Request $request): string
    {
        $token = (string) $request->session()->get('bmf_cart_token', '');
        if ($token === '') {
            $token = \Illuminate\Support\Str::uuid()->toString();
            $request->session()->put('bmf_cart_token', $token);
        }

        return $token;
    }
}
