@php
    use App\Support\Money;
    $subtotal = 0;
    foreach ($sampleProducts as $item) {
        $subtotal += ($item->defaultVariant?->price ?? 0);
    }
    $shipping = Money::fromRupees(350);
    $total = $subtotal + $shipping;
@endphp

<x-layouts.public
    title="Cart & Checkout — BIN MALIK FITNESS (PKR)"
    description="Guest and account checkout wireframe with Cash on Delivery and Manual Bank Transfer payment verification."
>
    <section class="surface-paper py-12">
        <div class="container-site space-y-8">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-graphite/12 pb-6">
                <div>
                    <span class="eyebrow text-gold-dark">Transactional Checkout Architecture · PKR</span>
                    <h1 class="display-l text-graphite mt-1">Shopping Cart & Checkout</h1>
                </div>
                <x-ui.demo-flag label="Stage 1 Cart & Checkout Wireframe" />
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                {{-- Left: Cart Items + Shipping & Payment Form --}}
                <div class="lg:col-span-7 space-y-6" x-data="{ paymentMethod: 'cod', checkoutMode: 'guest' }">
                    {{-- Cart items --}}
                    <div class="rounded-sm border border-graphite/15 bg-white p-6 space-y-4">
                        <h2 class="font-display text-2xl uppercase text-graphite">1. Items in Cart (Atomic Stock Reserved on Submit)</h2>
                        <div class="divide-y divide-graphite/10">
                            @foreach ($sampleProducts as $product)
                                @php $v = $product->defaultVariant; @endphp
                                <div class="flex items-center justify-between gap-4 py-4">
                                    <div class="flex items-center gap-4">
                                        <img src="{{ $product->primaryImage?->path }}" alt="{{ $product->name }}" class="h-16 w-16 rounded-xs bg-paper object-cover" />
                                        <div>
                                            <h3 class="text-sm font-bold text-graphite">{{ $product->name }}</h3>
                                            <p class="text-xs text-graphite/70">SKU: {{ $v?->sku }} · {{ $v?->size }} · {{ $v?->flavor }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <x-ui.price :paisa="$v?->price ?? 0" surface="light" size="sm" />
                                        <span class="block text-[11px] text-graphite/60">Qty: 1</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Shipping Address --}}
                    <div class="rounded-sm border border-graphite/15 bg-white p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-display text-2xl uppercase text-graphite">2. Delivery Details (Pakistan)</h2>
                            <div class="flex gap-2 text-xs">
                                <button type="button" @click="checkoutMode = 'guest'" :class="checkoutMode === 'guest' ? 'bg-graphite text-gold' : 'bg-paper text-graphite'" class="rounded-xs px-3 py-1 font-semibold uppercase">Guest Checkout</button>
                                <button type="button" @click="checkoutMode = 'account'" :class="checkoutMode === 'account' ? 'bg-graphite text-gold' : 'bg-paper text-graphite'" class="rounded-xs px-3 py-1 font-semibold uppercase">Saved Address</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="chk-name" class="field-label">Recipient Full Name</label>
                                <input id="chk-name" type="text" class="field-input" value="Demo Customer" />
                            </div>
                            <div>
                                <label for="chk-phone" class="field-label">Mobile Number (Pakistan)</label>
                                <input id="chk-phone" type="tel" class="field-input" value="0300-0000003" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="chk-address" class="field-label">House / Street / Sector</label>
                                <input id="chk-address" type="text" class="field-input" placeholder="Street address, sector/block, landmark" />
                            </div>
                            <div>
                                <label for="chk-city" class="field-label">City</label>
                                <input id="chk-city" type="text" class="field-input" value="Lahore" />
                            </div>
                            <div>
                                <label for="chk-province" class="field-label">Province / Territory</label>
                                <select id="chk-province" class="field-input">
                                    <option>Punjab</option>
                                    <option>Sindh</option>
                                    <option>Khyber Pakhtunkhwa</option>
                                    <option>Islamabad Capital Territory</option>
                                    <option>Balochistan</option>
                                    <option>Azad Jammu & Kashmir</option>
                                    <option>Gilgit-Baltistan</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="rounded-sm border border-graphite/15 bg-white p-6 space-y-4">
                        <h2 class="font-display text-2xl uppercase text-graphite">3. Payment Method (No Fake Auto-Paid States)</h2>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xs border p-4" :class="paymentMethod === 'cod' ? 'border-graphite bg-paper' : 'border-graphite/15'">
                                <input type="radio" name="payment_method" value="cod" x-model="paymentMethod" class="mt-1" />
                                <div class="text-xs">
                                    <span class="font-bold uppercase text-graphite block">Cash on Delivery (COD)</span>
                                    <span class="text-graphite/70">Order status transitions to <em>COD Confirmed</em> after phone verification.</span>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-xs border p-4" :class="paymentMethod === 'bank' ? 'border-graphite bg-paper' : 'border-graphite/15'">
                                <input type="radio" name="payment_method" value="bank" x-model="paymentMethod" class="mt-1" />
                                <div class="text-xs">
                                    <span class="font-bold uppercase text-graphite block">Manual Bank Transfer</span>
                                    <span class="text-graphite/70">Status remains <em>Awaiting Payment Verification</em> until staff verifies receipt.</span>
                                </div>
                            </label>
                        </div>

                        <div x-show="paymentMethod === 'bank'" x-cloak class="rounded-xs border border-gold-dark/40 bg-paper p-4 text-xs space-y-1.5">
                            <p class="font-bold text-gold-dark uppercase">Non-Negotiable Verification Safeguard</p>
                            <p class="text-graphite/80">
                                Uploading a transfer screenshot never marks an order as paid automatically. An Admin or authorized Manager must verify the bank reference before stock is committed or digital entitlements are granted.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Right: Immutable Financial Snapshot Summary --}}
                <aside class="lg:col-span-5">
                    <div class="sticky top-24 rounded-sm border border-graphite/15 bg-white p-6 space-y-5">
                        <h2 class="font-display text-2xl uppercase text-graphite">Order Summary (Server-Validated)</h2>

                        <dl class="space-y-2.5 text-sm border-y border-graphite/10 py-4">
                            <div class="flex justify-between">
                                <dt class="text-graphite/70">Subtotal (Server Recomputed)</dt>
                                <dd class="font-semibold tabular-nums">{{ Money::formatPaisa($subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-graphite/70">Nationwide Courier Shipping</dt>
                                <dd class="font-semibold tabular-nums">{{ Money::formatPaisa($shipping) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-graphite/10 pt-2.5 text-base font-bold">
                                <dt>Total Payable (PKR)</dt>
                                <dd class="tabular-nums">{{ Money::formatPaisa($total) }}</dd>
                            </div>
                        </dl>

                        <div class="space-y-2">
                            <label for="coupon-code" class="field-label text-xs">Promotional Coupon Code</label>
                            <div class="flex gap-2">
                                <input id="coupon-code" type="text" placeholder="Enter approved code" class="field-input" />
                                <x-ui.button variant="outline-dark" size="sm">Apply</x-ui.button>
                            </div>
                        </div>

                        <x-ui.button variant="graphite" size="lg" class="w-full">
                            Place Order (Idempotency Key Guarded)
                        </x-ui.button>

                        <p class="text-[11px] text-graphite/65 leading-relaxed">
                            Order lifecycle: <code class="font-mono">Pending → Awaiting Payment / COD Confirmed → Processing → Shipped → Delivered</code>.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.public>
