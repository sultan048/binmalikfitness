@php
    use App\Support\Money;
    $addr = $order->shipping_address_snapshot ?? [];
@endphp
<x-layouts.public
    :title="'Invoice '.$order->order_number.' — BIN MALIK FITNESS'"
    description="Printable PKR tax and commercial invoice for BIN MALIK FITNESS."
>
    <section class="surface-shop bg-paper text-graphite py-12">
        <div class="container-site max-w-4xl">
            <div class="rounded-sm border border-graphite/20 bg-white p-8 sm:p-12 shadow-xs space-y-8">
                <div class="flex flex-col justify-between gap-6 border-b-2 border-ink pb-6 sm:flex-row sm:items-start">
                    <div>
                        <span class="inline-block rounded-xs bg-ink px-2.5 py-1 font-display text-xl tracking-wider text-gold">
                            BIN MALIK FITNESS
                        </span>
                        <h1 class="mt-2 font-display text-4xl uppercase tracking-wide text-ink">
                            Commercial Order Invoice
                        </h1>
                        <p class="text-xs text-graphite/70">
                            {{ $brandSettings['gym.address'] ?? 'Facility Address Pending Owner Confirmation' }} · {{ $brandSettings['gym.city'] ?? 'Pakistan' }}
                        </p>
                        <p class="text-xs text-graphite/70">
                            {{ $brandSettings['gym.email'] ?? 'info@binmalikfitness.com' }} · {{ $brandSettings['gym.phone'] ?? '+92 300 0000000' }}
                        </p>
                    </div>
                    <div class="text-left sm:text-right text-xs space-y-1">
                        <p class="font-mono text-sm font-bold text-ink">INVOICE #{{ $order->order_number }}</p>
                        <p><strong>Issued:</strong> {{ $order->placed_at?->format('d M Y H:i') }}</p>
                        <p><strong>Order Status:</strong> {{ strtoupper(str_replace('_', ' ', $order->status)) }}</p>
                        <p><strong>Payment Status:</strong> {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}</p>
                        <p><strong>Currency:</strong> PKR (Pakistani Rupee)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 text-xs">
                    <div class="rounded-xs bg-paper p-4 space-y-1">
                        <p class="font-bold uppercase tracking-wider text-graphite/60">Billed & Shipped To</p>
                        <p class="font-bold text-sm text-ink">{{ $addr['recipient_name'] ?? $order->customer_name }}</p>
                        <p>{{ $addr['line1'] ?? '' }}{{ !empty($addr['area']) ? ', '.$addr['area'] : '' }}</p>
                        <p>{{ $addr['city'] ?? '' }}, {{ $addr['province'] ?? '' }} {{ $addr['postal_code'] ?? '' }}</p>
                        <p>Phone: {{ $addr['phone'] ?? $order->customer_phone }} · Email: {{ $order->customer_email }}</p>
                    </div>
                    <div class="rounded-xs bg-paper p-4 space-y-1">
                        <p class="font-bold uppercase tracking-wider text-graphite/60">Payment & Dispatch Summary</p>
                        <p><strong>Payment Method:</strong> {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</p>
                        <p><strong>Shipping Method:</strong> {{ strtoupper(str_replace('_', ' ', $order->shipping_method)) }}</p>
                        @if ($order->tracking_number)
                            <p><strong>Courier Tracking:</strong> {{ $order->courier_name }} — {{ $order->tracking_number }}</p>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b-2 border-ink text-[11px] uppercase text-ink">
                                <th class="py-2.5 pr-3">Item Description</th>
                                <th class="py-2.5 pr-3">SKU</th>
                                <th class="py-2.5 pr-3">Lot / Expiry</th>
                                <th class="py-2.5 pr-3">Qty</th>
                                <th class="py-2.5 pr-3">Unit Price</th>
                                <th class="py-2.5 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-graphite/15">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="py-3 pr-3">
                                        <strong class="text-ink">{{ $item->product_name_snapshot }}</strong>
                                        <span class="block text-graphite/65">{{ $item->variant_label_snapshot }}</span>
                                    </td>
                                    <td class="py-3 pr-3 font-mono">{{ $item->sku_snapshot }}</td>
                                    <td class="py-3 pr-3 font-mono">
                                        {{ $item->batch_number_snapshot ?? '—' }}
                                        @if ($item->expiry_date_snapshot)
                                            <span class="block text-[10px] text-graphite/60">Exp: {{ $item->expiry_date_snapshot->format('M Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-3 font-bold">{{ $item->quantity }}</td>
                                    <td class="py-3 pr-3">{{ Money::format($item->unit_price_paisa) }}</td>
                                    <td class="py-3 text-right font-bold text-ink">{{ Money::format($item->line_total_paisa) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col items-end border-t-2 border-ink pt-4 text-xs space-y-1.5">
                    <div class="flex w-full max-w-xs justify-between">
                        <span class="text-graphite/70">Subtotal:</span>
                        <span class="font-semibold">{{ Money::format($order->subtotal_paisa) }}</span>
                    </div>
                    @if ($order->discount_paisa > 0)
                        <div class="flex w-full max-w-xs justify-between text-success">
                            <span>Discount ({{ $order->coupon_code_snapshot }}):</span>
                            <span class="font-bold">-{{ Money::format($order->discount_paisa) }}</span>
                        </div>
                    @endif
                    <div class="flex w-full max-w-xs justify-between">
                        <span class="text-graphite/70">Shipping:</span>
                        <span class="font-semibold">{{ Money::format($order->shipping_paisa) }}</span>
                    </div>
                    <div class="flex w-full max-w-xs justify-between border-t border-graphite/20 pt-2 text-sm font-bold text-ink">
                        <span>Total (PKR):</span>
                        <span>{{ Money::format($order->total_paisa) }}</span>
                    </div>
                    @if ($order->refunded_paisa > 0)
                        <div class="flex w-full max-w-xs justify-between text-danger font-bold">
                            <span>Refunded:</span>
                            <span>{{ Money::format($order->refunded_paisa) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-graphite/10 pt-6 text-xs">
                    <a
                        href="{{ route('orders.confirmation', ['orderNumber' => $order->order_number, 'token' => $order->access_token]) }}"
                        class="font-bold uppercase tracking-wider text-gold-dark hover:underline"
                    >
                        ← Back to Order Summary
                    </a>
                    <button
                        type="button"
                        onclick="window.print()"
                        class="btn-primary-light px-4 py-2 text-xs cursor-pointer"
                    >
                        Print / Save PDF Invoice
                    </button>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
