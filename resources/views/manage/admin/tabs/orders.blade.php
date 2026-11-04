{{-- Tab panel "orders" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
@php
    use App\Support\Money;
@endphp
<section id="orders-fulfillment" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-5" data-tab-panel="orders" x-show="tab === 'orders'" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="font-display text-2xl uppercase text-graphite">Orders, Payment Verification & Refunds (PKR)</h2>
            <p class="text-xs text-graphite/70">
                Reconciled Verified Revenue: <strong>{{ Money::format($verifiedRevenuePaisa) }}</strong>. Bank transfer orders require explicit staff verification before shipping. Refunds are Admin-only.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('manage.admin') }}#orders-fulfillment" class="flex flex-wrap items-center gap-1.5 text-xs">
                <input type="text" name="order_search" value="{{ $filterOrderSearch }}" placeholder="Order # or customer..." class="field-input py-1 text-xs w-44" />
                <select name="order_status" class="field-input py-1 text-xs">
                    <option value="">All Statuses</option>
                    @foreach (['awaiting_payment', 'cod_confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $st)
                        <option value="{{ $st }}" @selected($filterOrderStatus === $st)>{{ strtoupper($st) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xs bg-charcoal px-2.5 py-1 font-bold uppercase text-gold cursor-pointer">Search</button>
            </form>
            <x-ui.badge tone="gold">{{ $orders->total() }} Orders</x-ui.badge>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-graphite/15 text-gold-dark uppercase">
                    <th class="py-2.5 pr-3">Order & Customer</th>
                    <th class="py-2.5 px-3">Total & Method</th>
                    <th class="py-2.5 px-3">Payment Verification</th>
                    <th class="py-2.5 px-3">Fulfillment Lifecycle</th>
                    <th class="py-2.5 pl-3">Admin Refund (payments.refund)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-graphite/10">
                @foreach ($orders as $ord)
                    @php $latestPayment = $ord->payments->first(); @endphp
                    <tr>
                        <td class="py-3 pr-3">
                            <a href="{{ route('orders.confirmation', ['orderNumber' => $ord->order_number]) }}" class="font-mono font-bold text-graphite hover:text-gold-dark">
                                {{ $ord->order_number }}
                            </a>
                            <span class="block text-graphite/70">{{ $ord->customer_name }} ({{ $ord->customer_phone }})</span>
                            <a href="{{ route('orders.invoice', ['orderNumber' => $ord->order_number]) }}" class="text-[11px] font-semibold text-gold-dark hover:underline">
                                Invoice →
                            </a>
                        </td>
                        <td class="py-3 px-3">
                            <strong class="block text-graphite">{{ Money::format($ord->total_paisa) }}</strong>
                            <span class="text-[11px] uppercase text-graphite/65">{{ str_replace('_', ' ', $ord->payment_method) }}</span>
                            @if ($ord->refunded_paisa > 0)
                                <span class="block text-[11px] font-bold text-danger">Refunded: {{ Money::format($ord->refunded_paisa) }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 space-y-1.5">
                            <span class="inline-block rounded-xs px-2 py-0.5 text-[10px] font-bold uppercase {{ $ord->payment_status === 'verified' ? 'bg-success/15 text-success' : 'bg-gold/25 text-ink' }}">
                                {{ str_replace('_', ' ', $ord->payment_status) }}
                            </span>
                            @if ($latestPayment && $latestPayment->transaction_reference)
                                <span class="block font-mono text-[11px] text-graphite/70">Ref: {{ $latestPayment->transaction_reference }}</span>
                            @endif
                            @if ($latestPayment && $latestPayment->status !== 'verified')
                                <form method="POST" action="{{ route('manage.payments.verify', $latestPayment) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="staff_notes" value="Verified by Admin" />
                                    <button type="submit" class="rounded-xs bg-ink px-2.5 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">
                                        Verify Payment
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <span class="block font-bold uppercase text-graphite mb-1">{{ str_replace('_', ' ', $ord->status) }}</span>
                            @if (! in_array($ord->status, ['delivered', 'cancelled', 'refunded', 'returned'], true))
                                <form method="POST" action="{{ route('manage.orders.status.update', $ord) }}" class="flex flex-wrap items-center gap-1">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="field-input py-1 text-[11px] w-28">
                                        <option value="processing" @selected($ord->status === 'processing')>Processing</option>
                                        <option value="shipped" @selected($ord->status === 'shipped')>Shipped</option>
                                        <option value="delivered" @selected($ord->status === 'delivered')>Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <input type="text" name="tracking_number" value="{{ $ord->tracking_number }}" placeholder="Tracking #" class="field-input py-1 text-[11px] w-28" />
                                    <button type="submit" class="rounded-xs border border-graphite px-2 py-1 text-[10px] font-bold uppercase text-graphite hover:bg-graphite hover:text-gold cursor-pointer">
                                        Update
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td class="py-3 pl-3">
                            @if (in_array($ord->payment_status, ['verified', 'partially_refunded'], true) && ($ord->total_paisa - $ord->refunded_paisa) > 0)
                                <form method="POST" action="{{ route('manage.orders.refunds.store', $ord) }}" class="flex flex-wrap items-center gap-1">
                                    @csrf
                                    <input type="number" name="amount_pkr" value="{{ Money::toRupees($ord->total_paisa - $ord->refunded_paisa) }}" min="1" step="1" class="field-input py-1 text-[11px] w-24" required />
                                    <input type="text" name="reason" value="Admin authorized refund" class="field-input py-1 text-[11px] w-32" required />
                                    <button type="submit" class="rounded-xs border border-danger px-2 py-1 text-[10px] font-bold uppercase text-danger hover:bg-danger hover:text-white cursor-pointer">
                                        Refund PKR
                                    </button>
                                </form>
                            @else
                                <span class="text-[11px] text-graphite/50">Requires verified payment</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</section>
