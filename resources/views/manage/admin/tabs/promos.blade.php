{{-- Tab panel "promos" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
@php
    use App\Support\Money;
@endphp
<div id="coupons-reviews" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="promos" x-show="tab === 'promos'" x-cloak>
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h2 class="font-display text-2xl uppercase text-graphite">Coupons & Promotional Codes</h2>
        <form method="POST" action="{{ route('manage.coupons.store') }}" class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 border-b border-graphite/10 pb-4 text-xs">
            @csrf
            <input type="text" name="code" placeholder="CODE20" required class="field-input text-xs uppercase" />
            <select name="discount_type" class="field-input text-xs">
                <option value="percentage">% Off</option>
                <option value="fixed_amount">Fixed PKR</option>
            </select>
            <input type="number" name="discount_value" placeholder="15" min="1" required class="field-input text-xs" />
            <x-ui.button type="submit" variant="graphite" size="sm">Create Coupon</x-ui.button>
        </form>
        <div class="space-y-1.5 text-xs">
            @foreach ($coupons as $cp)
                <div class="flex items-center justify-between rounded-xs bg-paper px-3 py-2">
                    <span>
                        <code class="font-mono font-bold text-gold-dark">{{ $cp->code }}</code> —
                        {{ $cp->discount_type === 'percentage' ? $cp->discount_value.'% off' : Money::format($cp->discount_value).' off' }}
                        (Min: {{ Money::format($cp->min_subtotal_paisa) }})
                    </span>
                    <span class="text-graphite/60">Used: {{ $cp->used_count }}{{ $cp->usage_limit ? '/'.$cp->usage_limit : '' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h2 class="font-display text-2xl uppercase text-graphite">Verified Reviews & Return Requests</h2>
        @if ($reviews->isEmpty() && $returnRequests->isEmpty())
            <p class="text-xs text-graphite/70">No pending product reviews or return requests in queue.</p>
        @endif
        @foreach ($reviews as $rev)
            <div class="flex items-center justify-between gap-2 rounded-xs border border-graphite/10 bg-paper p-3 text-xs">
                <div>
                    <strong>{{ $rev->title }} ({{ $rev->rating }}★)</strong> on {{ $rev->product?->name }}
                    <span class="block text-graphite/70">{{ $rev->body }} — Status: <strong>{{ strtoupper($rev->status) }}</strong></span>
                </div>
                <form method="POST" action="{{ route('manage.reviews.moderate', $rev) }}" class="flex gap-1">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="approved" />
                    <button type="submit" class="rounded-xs bg-success px-2.5 py-1 text-[10px] font-bold uppercase text-white cursor-pointer">Approve</button>
                </form>
            </div>
        @endforeach
        @foreach ($returnRequests as $ret)
            <div class="flex items-center justify-between gap-2 rounded-xs border border-graphite/10 bg-paper p-3 text-xs">
                <div>
                    <strong>Return #{{ $ret->id }} ({{ $ret->order?->order_number }})</strong>: {{ $ret->reason }}
                    <span class="block text-graphite/70">Status: <strong>{{ strtoupper($ret->status) }}</strong></span>
                </div>
                @if ($ret->status === 'requested')
                    <form method="POST" action="{{ route('manage.returns.resolve', $ret) }}" class="flex items-center gap-1">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="received_and_restocked" />
                        <input type="hidden" name="resolution_notes" value="Approved and restocked by Admin" />
                        <input type="hidden" name="restock_items" value="1" />
                        <button type="submit" class="rounded-xs bg-ink px-2.5 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">Approve & Restock</button>
                    </form>
                @endif
            </div>
        @endforeach
        {{ $reviews->links() }}
        {{ $returnRequests->links() }}
    </section>
</div>
