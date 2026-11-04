{{-- Tab panel "orders" of account/dashboard (included from the parent view, shares its variables) --}}
@php
    use App\Support\Money;
@endphp
<div id="my-orders-section" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="orders" x-show="tab === 'orders'" x-cloak>
    <section class="lg:col-span-8 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-graphite">My Supplement Orders & PKR Invoices</h2>
            <div class="flex items-center gap-4">
                @if ($orders->isNotEmpty())
                    <a href="{{ route('account.orders.export') }}" class="text-xs font-bold uppercase text-graphite hover:underline">Download CSV</a>
                @endif
                <a href="{{ route('shop.index') }}" class="text-xs font-bold uppercase text-gold-dark hover:underline">Shop Supplements →</a>
            </div>
        </div>
        @if ($orders->isEmpty())
            <p class="text-xs text-graphite/70">You have not placed any supplement orders yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-graphite/15 text-gold-dark uppercase">
                            <th class="py-2 pr-3">Order #</th>
                            <th class="py-2 px-3">Date</th>
                            <th class="py-2 px-3">Status & Payment</th>
                            <th class="py-2 px-3">Total (PKR)</th>
                            <th class="py-2 pl-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite/10">
                        @foreach ($orders as $ord)
                            <tr>
                                <td class="py-2.5 pr-3 font-mono font-bold text-graphite">{{ $ord->order_number }}</td>
                                <td class="py-2.5 px-3">{{ $ord->placed_at?->format('d M Y') }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="font-bold uppercase">{{ str_replace('_', ' ', $ord->status) }}</span>
                                    <span class="block text-[11px] text-graphite/65">Payment: {{ str_replace('_', ' ', $ord->payment_status) }}</span>
                                </td>
                                <td class="py-2.5 px-3 font-bold">{{ Money::format($ord->total_paisa) }}</td>
                                <td class="py-2.5 pl-3 space-x-2">
                                    <a href="{{ route('orders.confirmation', ['orderNumber' => $ord->order_number]) }}" class="font-semibold text-gold-dark hover:underline">Manage</a>
                                    <a href="{{ route('orders.invoice', ['orderNumber' => $ord->order_number]) }}" class="font-semibold text-graphite hover:underline">Invoice</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="lg:col-span-4 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h2 class="font-display text-2xl uppercase text-graphite">Saved Wishlist ({{ $wishlistItems->count() }})</h2>
        @if ($wishlistItems->isEmpty())
            <p class="text-xs text-graphite/70">Save supplements from the shop to track flavors and restocks here.</p>
        @else
            <div class="space-y-2.5 text-xs">
                @foreach ($wishlistItems as $w)
                    @if ($w->product)
                        <div class="flex items-center justify-between gap-2 rounded-xs bg-paper p-3">
                            <div>
                                <a href="{{ route('products.show', $w->product->slug) }}" class="font-bold text-graphite hover:text-gold-dark">{{ $w->product->name }}</a>
                                <span class="block text-gold-dark font-semibold">{{ $w->product->defaultVariant ? Money::format($w->product->defaultVariant->price) : '' }}</span>
                            </div>
                            <form method="POST" action="{{ route('wishlist.toggle') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $w->product_id }}" />
                                <button type="submit" class="text-danger font-semibold hover:underline cursor-pointer">Remove</button>
                            </form>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </section>
</div>
