@php use App\Support\Money; @endphp
<x-layouts.public
    title="Track Your Order — BIN MALIK FITNESS"
    description="Look up your supplement order status, payment verification state, and Pakistan courier tracking number."
>
    <section class="bg-ink text-ivory border-b border-white/10 py-10">
        <div class="container-site">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-gold">
                Real-Time Order & Courier Lookup
            </p>
            <h1 class="mt-2 font-display text-5xl uppercase tracking-wide text-ivory">
                Track Supplement Order
            </h1>
        </div>
    </section>

    <section class="surface-shop bg-paper text-graphite py-12">
        <div class="container-site max-w-3xl space-y-8">
            <form method="GET" action="{{ route('orders.track') }}" class="rounded-sm border border-graphite/12 bg-white p-6 sm:p-8 space-y-4">
                <h2 class="font-display text-2xl uppercase tracking-wide text-graphite">
                    Enter Order Number & Contact
                </h2>
                <p class="text-xs text-graphite/70">
                    Demo sample: Order <code>BMF-DEMO-1001</code> with email <code>customer.demo@binmalikfitness.test</code> or phone <code>0300-0000003</code>.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="track-order-num" class="field-label">Order Number</label>
                        <input
                            id="track-order-num"
                            type="text"
                            name="order_number"
                            value="{{ request('order_number') }}"
                            required
                            placeholder="BMF-DEMO-1001"
                            class="field-input uppercase"
                        />
                    </div>
                    <div>
                        <label for="track-contact" class="field-label">Customer Email or Mobile Number</label>
                        <input
                            id="track-contact"
                            type="text"
                            name="contact"
                            value="{{ request('contact') }}"
                            required
                            placeholder="customer.demo@binmalikfitness.test"
                            class="field-input"
                        />
                    </div>
                </div>
                <button type="submit" class="btn-primary-light text-xs cursor-pointer">
                    Look Up Order Status
                </button>
            </form>

            @if ($lookupError)
                <div role="alert" class="rounded-xs border border-danger/40 bg-danger/10 px-4 py-3 text-xs font-semibold text-danger">
                    {{ $lookupError }}
                </div>
            @endif

            @if ($order)
                <div class="rounded-sm border border-graphite/12 bg-white p-6 sm:p-8 space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-graphite/10 pb-4">
                        <div>
                            <span class="font-mono text-xs font-bold text-gold-dark">{{ $order->order_number }}</span>
                            <h3 class="font-display text-3xl uppercase text-graphite">
                                Status: {{ str_replace('_', ' ', $order->status) }}
                            </h3>
                        </div>
                        <a
                            href="{{ route('orders.confirmation', ['orderNumber' => $order->order_number]) }}"
                            class="btn-primary-light text-xs"
                        >
                            Open Full Order & Invoice →
                        </a>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-xs">
                        <div class="rounded-xs bg-paper p-3">
                            <span class="block text-[10px] uppercase text-graphite/60">Payment Status</span>
                            <strong class="uppercase text-graphite">{{ str_replace('_', ' ', $order->payment_status) }}</strong>
                        </div>
                        <div class="rounded-xs bg-paper p-3">
                            <span class="block text-[10px] uppercase text-graphite/60">Courier & Tracking</span>
                            <strong class="font-mono text-graphite">{{ $order->courier_name ?? 'Pending Dispatch' }} {{ $order->tracking_number ? '· '.$order->tracking_number : '' }}</strong>
                        </div>
                        <div class="rounded-xs bg-paper p-3">
                            <span class="block text-[10px] uppercase text-graphite/60">Total (PKR)</span>
                            <strong class="text-graphite">{{ Money::format($order->total_paisa) }}</strong>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
