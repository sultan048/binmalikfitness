@php
    use App\Support\Money;
    use Illuminate\Support\Str;
    $idempotencyKey = 'chk-'.Str::uuid()->toString();
    $provinces = ['Punjab', 'Sindh', 'Islamabad Capital Territory', 'Khyber Pakhtunkhwa', 'Balochistan', 'Azad Kashmir', 'Gilgit-Baltistan'];
@endphp
<x-layouts.public
    title="Cart & Checkout (PKR) — BIN MALIK FITNESS"
    description="Review your supplement cart, apply coupon codes, and complete Cash on Delivery or Manual Bank Transfer checkout in Pakistan."
>
    <section class="bg-ink text-ivory border-b border-white/10 py-10">
        <div class="container-site flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-gold">
                    Server-Validated PKR Pricing · Atomic Stock Reservation · Idempotent Checkout
                </p>
                <h1 class="mt-2 font-display text-5xl uppercase tracking-wide text-ivory">
                    Cart & Pakistan Checkout
                </h1>
            </div>
            <a href="{{ route('shop.index') }}" class="text-xs font-semibold uppercase tracking-wider text-gold hover:underline">
                ← Continue Supplement Shopping
            </a>
        </div>
    </section>

    <section class="surface-shop bg-paper text-graphite py-12">
        <div class="container-site space-y-8">
            @if (session('status'))
                <div role="status" class="rounded-xs border border-success/40 bg-success/10 px-4 py-3 text-xs font-semibold text-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xs border border-danger/40 bg-danger/10 px-4 py-3 text-xs text-danger">
                    <p class="font-bold uppercase mb-1">Please review the following issues:</p>
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (empty($totals['lines']))
                <div class="rounded-sm border border-graphite/15 bg-white p-12 text-center space-y-4">
                    <h2 class="font-display text-4xl uppercase text-graphite">Your Cart Is Currently Empty</h2>
                    <p class="mx-auto max-w-lg text-sm text-graphite/70">
                        Browse batch-tracked whey protein, micronized creatine, pre-workout formulas, and training accessories in PKR.
                    </p>
                    <div class="flex flex-wrap justify-center gap-3 pt-2">
                        <a href="{{ route('shop.index') }}" class="btn-primary-light text-xs">Explore Supplement Shop</a>
                        <a href="{{ route('orders.track') }}" class="rounded-xs border border-graphite/25 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-graphite hover:bg-graphite/5">
                            Track an Existing Order
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                    <!-- Left Column: Cart Lines + Coupon + Checkout Form -->
                    <div class="lg:col-span-7 space-y-8">
                        <!-- Free Shipping Banner -->
                        <div class="rounded-sm border border-graphite/12 bg-white p-4 flex items-center justify-between text-xs">
                            @if ($totals['amount_to_free_shipping_paisa'] > 0)
                                <span>
                                    Add <strong>{{ Money::format($totals['amount_to_free_shipping_paisa']) }}</strong> more for <strong>FREE Standard Pakistan Courier Delivery</strong>.
                                </span>
                            @else
                                <span class="font-bold text-success">
                                    ✓ Your order qualifies for FREE Standard Pakistan Courier Delivery!
                                </span>
                            @endif
                            <span class="font-mono text-[11px] text-graphite/60">Threshold: PKR 10,000</span>
                        </div>

                        <!-- Cart Items Table -->
                        <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                            <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">
                                1. Review Cart Items ({{ $totals['item_count'] }})
                            </h2>
                            <div class="divide-y divide-graphite/10">
                                @foreach ($totals['lines'] as $line)
                                    <div class="py-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <a href="{{ route('products.show', $line['product_slug']) }}" class="font-display text-xl uppercase text-graphite hover:text-gold-dark">
                                                {{ $line['product_name'] }}
                                            </a>
                                            <p class="text-xs text-graphite/70">
                                                Variant: <strong>{{ $line['variant_label'] }}</strong> · SKU: <code class="font-mono">{{ $line['sku'] }}</code>
                                            </p>
                                            <p class="text-[11px] text-graphite/55">
                                                Unit Price: {{ $line['unit_price_formatted'] }} · Lot: {{ $line['sample_lot_number'] ?? 'Tracked' }}
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <form method="POST" action="{{ route('cart.items.update', $line['cart_item_id']) }}" class="flex items-center gap-1.5">
                                                @csrf
                                                @method('PUT')
                                                <label for="qty-{{ $line['cart_item_id'] }}" class="sr-only">Quantity</label>
                                                <input
                                                    id="qty-{{ $line['cart_item_id'] }}"
                                                    type="number"
                                                    name="quantity"
                                                    value="{{ $line['quantity'] }}"
                                                    min="0"
                                                    max="{{ $line['available_stock'] }}"
                                                    class="w-16 rounded-xs border border-graphite/25 px-2 py-1 text-xs font-semibold text-graphite"
                                                />
                                                <button type="submit" class="rounded-xs border border-graphite/25 px-2.5 py-1 text-[11px] font-bold uppercase text-graphite hover:bg-graphite/5 cursor-pointer">
                                                    Update
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('cart.items.destroy', $line['cart_item_id']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-danger hover:underline cursor-pointer">
                                                    Remove
                                                </button>
                                            </form>

                                            <span class="min-w-[95px] text-right font-display text-xl text-graphite">
                                                {{ $line['line_total_formatted'] }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Checkout Details Form -->
                        <form
                            method="POST"
                            action="{{ route('checkout.place') }}"
                            x-data="{
                                useSavedAddress: '{{ $savedAddresses->first()?->id ?? '' }}',
                                paymentMethod: '{{ old('payment_method', 'cod') }}',
                                shippingMethod: '{{ old('shipping_method', $totals['shipping_method']) }}'
                            }"
                            class="rounded-sm border border-graphite/12 bg-white p-6 sm:p-8 space-y-6"
                        >
                            @csrf
                            <input type="hidden" name="_idempotency_key" value="{{ $idempotencyKey }}" />

                            <div class="border-b border-graphite/10 pb-3">
                                <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">
                                    2. Customer & Pakistan Delivery Information
                                </h2>
                                <p class="text-xs text-graphite/65">
                                    @auth
                                        Signed in as <strong>{{ auth()->user()->email }}</strong>. Select a saved address or enter a new one.
                                    @else
                                        Checking out as a Guest. Have an account? <a href="{{ route('login') }}" class="font-bold text-gold-dark underline">Sign in here</a>.
                                    @endauth
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="chk-name" class="field-label">Recipient Full Name</label>
                                    <input
                                        id="chk-name"
                                        type="text"
                                        name="customer_name"
                                        value="{{ old('customer_name', auth()->user()?->name) }}"
                                        required
                                        placeholder="Muhammad Bilal"
                                        class="field-input"
                                    />
                                </div>
                                <div>
                                    <label for="chk-email" class="field-label">Email Address</label>
                                    <input
                                        id="chk-email"
                                        type="email"
                                        name="customer_email"
                                        value="{{ old('customer_email', auth()->user()?->email) }}"
                                        required
                                        placeholder="bilal@example.com"
                                        class="field-input"
                                    />
                                </div>
                                <div>
                                    <label for="chk-phone" class="field-label">Pakistan Mobile (03XX)</label>
                                    <input
                                        id="chk-phone"
                                        type="text"
                                        name="customer_phone"
                                        value="{{ old('customer_phone', auth()->user()?->phone) }}"
                                        required
                                        placeholder="0300-1234567"
                                        class="field-input"
                                    />
                                </div>
                            </div>

                            @if ($savedAddresses->isNotEmpty())
                                <div>
                                    <label for="saved-address-select" class="field-label">Saved Pakistan Delivery Addresses</label>
                                    <select id="saved-address-select" name="saved_address_id" x-model="useSavedAddress" class="field-input">
                                        @foreach ($savedAddresses as $addr)
                                            <option value="{{ $addr->id }}">
                                                {{ $addr->label }}: {{ $addr->line1 }}, {{ $addr->city }}, {{ $addr->province }} ({{ $addr->phone }})
                                            </option>
                                        @endforeach
                                        <option value="">+ Enter a New Delivery Address</option>
                                    </select>
                                </div>
                            @endif

                            <div x-show="!useSavedAddress" class="space-y-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label for="chk-line1" class="field-label">House / Flat, Street & Sector</label>
                                        <input id="chk-line1" type="text" name="line1" value="{{ old('line1') }}" placeholder="House 14, Street 6, DHA Phase 5" class="field-input" />
                                    </div>
                                    <div>
                                        <label for="chk-area" class="field-label">Area / Block (Optional)</label>
                                        <input id="chk-area" type="text" name="area" value="{{ old('area') }}" placeholder="Sector C / Gulberg" class="field-input" />
                                    </div>
                                    <div>
                                        <label for="chk-city" class="field-label">City</label>
                                        <input id="chk-city" type="text" name="city" value="{{ old('city', 'Lahore') }}" placeholder="Lahore, Karachi, Islamabad..." class="field-input" />
                                    </div>
                                    <div>
                                        <label for="chk-province" class="field-label">Province / Territory</label>
                                        <select id="chk-province" name="province" class="field-input">
                                            @foreach ($provinces as $prov)
                                                <option value="{{ $prov }}" @selected(old('province', 'Punjab') === $prov)>{{ $prov }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="chk-postal" class="field-label">Postal Code (Optional)</label>
                                        <input id="chk-postal" type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="54000" class="field-input" />
                                    </div>
                                </div>

                                @auth
                                    <label class="flex items-center gap-2 text-xs text-graphite cursor-pointer">
                                        <input type="checkbox" name="save_address" value="1" checked class="rounded-xs border-graphite/30 text-ink focus:ring-gold" />
                                        <span>Save this address to my account for future orders</span>
                                    </label>
                                @endauth
                            </div>

                            <!-- Shipping Selection -->
                            <div class="pt-4 border-t border-graphite/10 space-y-3">
                                <h3 class="font-display text-xl uppercase tracking-wide text-graphite">3. Shipping Method</h3>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="flex items-start gap-3 rounded-xs border p-3.5 text-xs cursor-pointer" :class="shippingMethod === 'standard_courier' ? 'border-ink bg-paper' : 'border-graphite/15'">
                                        <input type="radio" name="shipping_method" value="standard_courier" x-model="shippingMethod" class="mt-0.5 text-ink focus:ring-gold" />
                                        <div>
                                            <span class="block font-bold uppercase text-graphite">Standard Nationwide Courier (2–4 Days)</span>
                                            <span class="text-graphite/70">PKR 350 (Free on net orders ≥ PKR 10,000)</span>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 rounded-xs border p-3.5 text-xs cursor-pointer" :class="shippingMethod === 'express_city' ? 'border-ink bg-paper' : 'border-graphite/15'">
                                        <input type="radio" name="shipping_method" value="express_city" x-model="shippingMethod" class="mt-0.5 text-ink focus:ring-gold" />
                                        <div>
                                            <span class="block font-bold uppercase text-graphite">Priority Express Dispatch (1–2 Days)</span>
                                            <span class="text-graphite/70">Flat PKR 650</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Method Selection -->
                            <div class="pt-4 border-t border-graphite/10 space-y-3">
                                <h3 class="font-display text-xl uppercase tracking-wide text-graphite">4. Payment Method (PKR)</h3>
                                <div class="space-y-2.5">
                                    <label class="flex items-start gap-3 rounded-xs border p-4 text-xs cursor-pointer" :class="paymentMethod === 'cod' ? 'border-ink bg-paper' : 'border-graphite/15'">
                                        <input type="radio" name="payment_method" value="cod" x-model="paymentMethod" class="mt-0.5 text-ink focus:ring-gold" />
                                        <div>
                                            <span class="block font-bold uppercase text-graphite">Cash on Delivery (COD — Pakistan)</span>
                                            <span class="text-graphite/75">Pay in PKR cash to the courier upon sealed parcel delivery. Stock is reserved immediately.</span>
                                        </div>
                                    </label>

                                    <label class="flex items-start gap-3 rounded-xs border p-4 text-xs cursor-pointer" :class="paymentMethod === 'bank_transfer' ? 'border-ink bg-paper' : 'border-graphite/15'">
                                        <input type="radio" name="payment_method" value="bank_transfer" x-model="paymentMethod" class="mt-0.5 text-ink focus:ring-gold" />
                                        <div class="space-y-1">
                                            <span class="block font-bold uppercase text-graphite">Manual Bank Transfer / IBFT (With Staff Verification)</span>
                                            <span class="block text-graphite/75">
                                                Place your order to lock stock, then upload your transfer receipt on the confirmation screen. Orders are marked paid only after staff verification.
                                            </span>
                                            <div x-show="paymentMethod === 'bank_transfer'" class="mt-2 rounded-xs border border-graphite/15 bg-white p-3 font-mono text-[11px] text-graphite space-y-0.5">
                                                <p><strong>Bank:</strong> {{ $bankSettings['payment.bank_name'] ?? 'Pending Owner Confirmation' }}</p>
                                                <p><strong>Title:</strong> {{ $bankSettings['payment.account_title'] ?? 'BIN MALIK FITNESS' }}</p>
                                                <p><strong>IBAN:</strong> {{ $bankSettings['payment.iban'] ?? 'PK00MEZN0000000000000000' }}</p>
                                            </div>
                                        </div>
                                    </label>

                                    <div class="flex items-start gap-3 rounded-xs border border-graphite/15 bg-graphite/5 p-3.5 text-xs opacity-75">
                                        <input type="radio" disabled class="mt-0.5" />
                                        <div>
                                            <span class="block font-bold uppercase text-graphite">Online Card / Wallet Gateway (Architecture Ready — Disabled Until Provider Confirmed)</span>
                                            <span class="text-graphite/70">PaymentGatewayInterface contract is wired; live card charging remains disabled until the provider & webhook secret are confirmed.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="chk-notes" class="field-label">Delivery Instructions (Optional)</label>
                                <textarea id="chk-notes" name="customer_notes" rows="2" class="field-input" placeholder="Call before delivery or gate drop-off instructions...">{{ old('customer_notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn-primary-light w-full py-3.5 text-sm cursor-pointer">
                                Place Order & Reserve Stock ({{ $totals['total_formatted'] }})
                            </button>
                        </form>
                    </div>

                    <!-- Right Column: Server-Side Order Summary & Coupon Box -->
                    <div class="lg:col-span-5 space-y-6">
                        <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-5 sticky top-24">
                            <h2 class="font-display text-2xl uppercase tracking-wide text-graphite border-b border-graphite/10 pb-3">
                                Server-Validated Order Summary
                            </h2>

                            <dl class="space-y-2.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <dt class="text-graphite/70">Items Subtotal ({{ $totals['item_count'] }} units)</dt>
                                    <dd class="font-semibold text-graphite">{{ $totals['subtotal_formatted'] }}</dd>
                                </div>

                                @if ($totals['discount_paisa'] > 0)
                                    <div class="flex items-center justify-between text-success">
                                        <dt class="font-semibold">Coupon Discount ({{ $totals['coupon_code_snapshot'] }})</dt>
                                        <dd class="font-bold">-{{ $totals['discount_formatted'] }}</dd>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between">
                                    <dt class="text-graphite/70">Estimated Shipping ({{ strtoupper(str_replace('_', ' ', $totals['shipping_method'])) }})</dt>
                                    <dd class="font-semibold text-graphite">{{ $totals['shipping_formatted'] }}</dd>
                                </div>

                                <div class="flex items-center justify-between">
                                    <dt class="text-graphite/70">Total Parcel Weight</dt>
                                    <dd class="font-mono text-graphite">{{ number_format($totals['total_weight_grams']) }} g</dd>
                                </div>

                                <div class="flex items-baseline justify-between border-t border-graphite/15 pt-3">
                                    <dt class="font-display text-2xl uppercase text-graphite">Total Payable (PKR)</dt>
                                    <dd class="font-display text-3xl text-graphite">{{ $totals['total_formatted'] }}</dd>
                                </div>
                            </dl>

                            <!-- Coupon Code Form -->
                            <div class="pt-4 border-t border-graphite/10 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-graphite">Promotional Coupon</span>
                                    <span class="text-[11px] text-graphite/60">Try <code class="font-mono font-bold text-gold-dark">BMF10</code> or <code class="font-mono font-bold text-gold-dark">DEMO1500</code></span>
                                </div>

                                @if ($totals['coupon_code_snapshot'])
                                    <div class="flex items-center justify-between rounded-xs border border-success/30 bg-success/10 px-3 py-2 text-xs">
                                        <span class="font-bold text-success">Applied: {{ $totals['coupon_code_snapshot'] }}</span>
                                        <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-bold text-danger hover:underline cursor-pointer">Remove</button>
                                        </form>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('cart.coupon.apply') }}" class="flex gap-2">
                                        @csrf
                                        <input
                                            type="text"
                                            name="coupon_code"
                                            placeholder="Enter coupon code"
                                            required
                                            class="field-input uppercase"
                                        />
                                        <button type="submit" class="rounded-xs bg-ink px-4 py-2 text-xs font-bold uppercase tracking-wider text-gold cursor-pointer">
                                            Apply
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="rounded-xs bg-paper p-3.5 text-[11px] text-graphite/75 space-y-1">
                                <p class="font-bold uppercase text-graphite">Commerce Integrity Guarantees</p>
                                <p>• Prices & discounts are recomputed server-side in integer paisa.</p>
                                <p>• Stock is reserved atomically in MySQL inside a database transaction.</p>
                                <p>• Bank transfer receipts are stored on private storage outside <code>/public</code>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
