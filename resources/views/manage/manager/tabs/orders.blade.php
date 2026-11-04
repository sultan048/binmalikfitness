{{-- Tab panel "orders" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
@php
    use App\Support\Money;
@endphp
<section id="manager-orders" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4" data-tab-panel="orders" x-show="tab === 'orders'" x-cloak>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="font-display text-2xl uppercase text-graphite">Order Fulfillment & Payment Verification Queue</h2>
            <p class="text-xs text-graphite/70">
                Managers can verify manual bank transfer receipts and dispatch orders with tracking numbers, but cannot issue refunds (<code class="font-mono">payments.refund</code> is Admin-only).
            </p>
        </div>
        <x-ui.badge tone="gold">{{ $orders->total() }} Orders</x-ui.badge>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-graphite/12 text-gold-dark uppercase">
                    <th class="py-2 pr-3">Order #</th>
                    <th class="py-2 px-3">Customer</th>
                    <th class="py-2 px-3">Total & Payment</th>
                    <th class="py-2 pl-3">Dispatch & Status Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-graphite/10">
                @foreach ($orders as $ord)
                    @php $pay = $ord->payments->first(); @endphp
                    <tr>
                        <td class="py-2.5 pr-3 font-mono font-bold text-graphite">{{ $ord->order_number }}</td>
                        <td class="py-2.5 px-3">{{ $ord->customer_name }} ({{ $ord->customer_phone }})</td>
                        <td class="py-2.5 px-3">
                            <strong>{{ Money::format($ord->total_paisa) }}</strong> · {{ strtoupper($ord->payment_status) }}
                            @if ($pay && $pay->status !== 'verified')
                                <form method="POST" action="{{ route('manage.payments.verify', $pay) }}" class="inline ml-2">
                                    @csrf
                                    <button type="submit" class="rounded-xs bg-ink px-2 py-0.5 text-[10px] font-bold uppercase text-gold cursor-pointer">Verify</button>
                                </form>
                            @endif
                        </td>
                        <td class="py-2.5 pl-3">
                            <form method="POST" action="{{ route('manage.orders.status.update', $ord) }}" class="flex items-center gap-1">
                                @csrf
                                @method('PUT')
                                <select name="status" class="field-input py-1 text-[11px] w-28">
                                    <option value="processing" @selected($ord->status === 'processing')>Processing</option>
                                    <option value="shipped" @selected($ord->status === 'shipped')>Shipped</option>
                                    <option value="delivered" @selected($ord->status === 'delivered')>Delivered</option>
                                </select>
                                <input type="text" name="tracking_number" value="{{ $ord->tracking_number }}" placeholder="Tracking #" class="field-input py-1 text-[11px] w-28" />
                                <button type="submit" class="rounded-xs border border-graphite px-2 py-1 text-[10px] font-bold uppercase text-graphite cursor-pointer">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</section>
