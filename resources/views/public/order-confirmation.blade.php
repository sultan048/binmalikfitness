@php
    use App\Models\Order;
    use App\Support\Money;
    $addr = $order->shipping_address_snapshot ?? [];
    $steps = [
        Order::STATUS_AWAITING_PAYMENT => 'Order Placed / Awaiting Verification',
        Order::STATUS_COD_CONFIRMED => 'COD Confirmed',
        Order::STATUS_PROCESSING => 'Processing & Packing',
        Order::STATUS_SHIPPED => 'Shipped via Courier',
        Order::STATUS_DELIVERED => 'Delivered',
    ];
@endphp
<x-layouts.public
    :title="'Order '.$order->order_number.' — BIN MALIK FITNESS'"
    description="Order confirmation, live fulfillment status, bank transfer proof upload, and PKR invoice."
>
    <section class="bg-ink text-ivory border-b border-white/10 py-10">
        <div class="container-site flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-gold">
                    Order Lifecycle & Financial Snapshot · Placed {{ $order->placed_at?->format('d M Y H:i') }}
                </p>
                <h1 class="mt-2 font-display text-5xl uppercase tracking-wide text-ivory">
                    Order {{ $order->order_number }}
                </h1>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a
                    href="{{ route('orders.invoice', ['orderNumber' => $order->order_number, 'token' => $order->access_token]) }}"
                    class="rounded-xs border border-gold bg-charcoal px-4 py-2 text-xs font-bold uppercase tracking-wider text-gold hover:bg-gold hover:text-ink"
                >
                    View Printable PKR Invoice
                </a>
                <a href="{{ route('shop.index') }}" class="rounded-xs border border-white/20 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-ivory hover:border-gold">
                    Back to Shop
                </a>
            </div>
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
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Order Lifecycle Status Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-sm border border-graphite/12 bg-white p-5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-graphite/60">Order Status</span>
                    <p class="mt-1 font-display text-3xl uppercase text-graphite">{{ str_replace('_', ' ', $order->status) }}</p>
                    @if ($order->tracking_number)
                        <p class="mt-1 text-xs font-mono text-gold-dark">{{ $order->courier_name }}: {{ $order->tracking_number }}</p>
                    @endif
                </div>
                <div class="rounded-sm border border-graphite/12 bg-white p-5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-graphite/60">Payment Status</span>
                    <p class="mt-1 font-display text-3xl uppercase {{ $order->payment_status === 'verified' ? 'text-success' : 'text-gold-dark' }}">
                        {{ str_replace('_', ' ', $order->payment_status) }}
                    </p>
                    <p class="mt-1 text-xs text-graphite/65">Method: {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</p>
                </div>
                <div class="rounded-sm border border-graphite/12 bg-white p-5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-graphite/60">Order Total (PKR)</span>
                    <p class="mt-1 font-display text-3xl text-graphite">{{ Money::format($order->total_paisa) }}</p>
                    @if ($order->refunded_paisa > 0)
                        <p class="mt-1 text-xs font-bold text-danger">Refunded: {{ Money::format($order->refunded_paisa) }}</p>
                    @endif
                </div>
                <div class="rounded-sm border border-graphite/12 bg-white p-5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-graphite/60">Delivery Destination</span>
                    <p class="mt-1 text-sm font-bold text-graphite">{{ $addr['recipient_name'] ?? $order->customer_name }}</p>
                    <p class="text-xs text-graphite/70">{{ $addr['line1'] ?? '' }}, {{ $addr['city'] ?? '' }}, {{ $addr['province'] ?? '' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Order Line Items Snapshot -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                        <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">
                            Immutable Order Item Snapshots
                        </h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-graphite/15 text-[11px] uppercase text-graphite/60">
                                        <th class="py-2 pr-3">Product & Variant</th>
                                        <th class="py-2 pr-3">Batch / Expiry</th>
                                        <th class="py-2 pr-3">Qty</th>
                                        <th class="py-2 pr-3">Unit Price</th>
                                        <th class="py-2 text-right">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-graphite/10">
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td class="py-3 pr-3">
                                                <p class="font-bold text-graphite">{{ $item->product_name_snapshot }}</p>
                                                <p class="text-[11px] text-graphite/65">
                                                    {{ $item->variant_label_snapshot }} · SKU: <code class="font-mono">{{ $item->sku_snapshot }}</code>
                                                </p>
                                            </td>
                                            <td class="py-3 pr-3 font-mono text-[11px]">
                                                {{ $item->batch_number_snapshot ?? 'N/A' }}
                                                @if ($item->expiry_date_snapshot)
                                                    <span class="block text-graphite/60">Exp: {{ $item->expiry_date_snapshot->format('M Y') }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-3 font-bold">{{ $item->quantity }}</td>
                                            <td class="py-3 pr-3">{{ Money::format($item->unit_price_paisa) }}</td>
                                            <td class="py-3 text-right font-bold text-graphite">{{ Money::format($item->line_total_paisa) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <dl class="border-t border-graphite/15 pt-4 space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <dt class="text-graphite/70">Subtotal</dt>
                                <dd class="font-semibold">{{ Money::format($order->subtotal_paisa) }}</dd>
                            </div>
                            @if ($order->discount_paisa > 0)
                                <div class="flex justify-between text-success">
                                    <dt>Coupon Discount ({{ $order->coupon_code_snapshot }})</dt>
                                    <dd class="font-bold">-{{ Money::format($order->discount_paisa) }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-graphite/70">Shipping ({{ strtoupper(str_replace('_', ' ', $order->shipping_method)) }})</dt>
                                <dd class="font-semibold">{{ Money::format($order->shipping_paisa) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-graphite/10 pt-2 text-sm font-bold text-graphite">
                                <dt>Total Snapshot</dt>
                                <dd>{{ Money::format($order->total_paisa) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Right Column: Bank Transfer Proof Upload, Cancellation & Return Actions -->
                <div class="lg:col-span-5 space-y-6">
                    @if ($order->payment_method === 'bank_transfer')
                        <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">
                                    Manual Bank Transfer Verification
                                </h2>
                                <span class="rounded-xs bg-paper px-2 py-0.5 text-[10px] font-bold uppercase text-gold-dark">
                                    Staff Verified Only
                                </span>
                            </div>

                            <div class="rounded-xs bg-paper p-3.5 text-xs space-y-1">
                                <p><strong>Bank:</strong> {{ $bankSettings['payment.bank_name'] ?? 'Pending Owner Confirmation' }}</p>
                                <p><strong>Account Title:</strong> {{ $bankSettings['payment.account_title'] ?? 'BIN MALIK FITNESS' }}</p>
                                <p><strong>IBAN:</strong> <code class="font-mono">{{ $bankSettings['payment.iban'] ?? 'PK00MEZN0000000000000000' }}</code></p>
                            </div>

                            @if ($order->payment_status === Order::PAYMENT_VERIFIED)
                                <div class="rounded-xs border border-success/40 bg-success/10 p-3 text-xs font-semibold text-success">
                                    ✓ Your bank transfer payment has been verified by our operations staff.
                                </div>
                            @else
                                <p class="text-xs text-graphite/75">
                                    Upload your bank transfer screenshot or PDF receipt below. <strong>Note:</strong> Uploading a receipt sets your payment status to <em>Proof Submitted</em>—an order is only marked paid after staff verification.
                                </p>
                                <form
                                    method="POST"
                                    action="{{ route('orders.payment-proof.store', $order->order_number) }}"
                                    enctype="multipart/form-data"
                                    class="space-y-3"
                                >
                                    @csrf
                                    <input type="hidden" name="token" value="{{ $order->access_token }}" />
                                    <div>
                                        <label for="proof-ref" class="field-label">Bank Transaction / RAAST Reference #</label>
                                        <input id="proof-ref" type="text" name="transaction_reference" required placeholder="MEEZAN-20261025-99182" class="field-input" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <div>
                                            <label for="proof-bank" class="field-label">Sender Bank Name</label>
                                            <input id="proof-bank" type="text" name="sender_bank_name" required placeholder="Meezan / HBL / SadaPay" class="field-input" />
                                        </div>
                                        <div>
                                            <label for="proof-last4" class="field-label">Sender Account Last 4</label>
                                            <input id="proof-last4" type="text" name="sender_account_last4" placeholder="4419" class="field-input" />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="proof-file" class="field-label">Receipt Image or PDF (Stored on Private Disk)</label>
                                        <input id="proof-file" type="file" name="receipt_file" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="field-input text-xs" />
                                    </div>
                                    <button type="submit" class="btn-primary-light w-full text-xs cursor-pointer">
                                        Submit Bank Transfer Receipt for Staff Review
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                    @if ($order->canCustomerCancel())
                        <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-3">
                            <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">Cancel Order Before Dispatch</h2>
                            <p class="text-xs text-graphite/70">
                                Need to cancel this order? Because it has not shipped yet, you can cancel immediately and release reserved stock.
                            </p>
                            <form method="POST" action="{{ route('orders.cancel', $order->order_number) }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="token" value="{{ $order->access_token }}" />
                                <div>
                                    <label for="cancel-reason" class="field-label">Cancellation Reason</label>
                                    <input id="cancel-reason" type="text" name="reason" required placeholder="Ordered wrong flavor / changing address" class="field-input" />
                                </div>
                                <button type="submit" class="rounded-xs border border-danger px-4 py-2 text-xs font-bold uppercase tracking-wider text-danger hover:bg-danger hover:text-white cursor-pointer">
                                    Cancel Order & Release Reserved Stock
                                </button>
                            </form>
                        </div>
                    @endif

                    @if ($order->canRequestReturn())
                        <div class="rounded-sm border border-graphite/12 bg-white p-6 space-y-3">
                            <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">Request Return / Seal Issue Review</h2>
                            <p class="text-xs text-graphite/70">
                                Delivered supplements are eligible for return review if unopened with original safety seal intact or in case of transit damage.
                            </p>
                            <form method="POST" action="{{ route('orders.return.store', $order->order_number) }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="token" value="{{ $order->access_token }}" />
                                <div>
                                    <label for="ret-reason" class="field-label">Return Reason</label>
                                    <input id="ret-reason" type="text" name="reason" required placeholder="Damaged outer box / wrong item received" class="field-input" />
                                </div>
                                <div>
                                    <label for="ret-details" class="field-label">Details & Batch Number</label>
                                    <textarea id="ret-details" name="details" rows="2" required class="field-input" placeholder="Describe the issue and confirm seal condition..."></textarea>
                                </div>
                                <button type="submit" class="btn-primary-light text-xs cursor-pointer">
                                    Submit Return Request
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
