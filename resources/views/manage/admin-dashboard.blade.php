@php
    use App\Support\Money;
    // Each 'tab' key matches a data-tab-panel below; 'group' is the sidebar category heading.
    $adminModules = [
        ['group' => 'Home', 'tab' => 'overview', 'label' => 'Overview & Analytics', 'badge' => $financialSummary['net_reconciled_formatted']],

        ['group' => 'Shop & Sales', 'tab' => 'orders', 'label' => 'Orders, Payments & Refunds', 'badge' => $orders->count()],
        ['group' => 'Shop & Sales', 'tab' => 'inventory', 'label' => 'Inventory & Products', 'badge' => $productsCount.'/'.$variants->count()],
        ['group' => 'Shop & Sales', 'tab' => 'promos', 'label' => 'Coupons, Reviews & Returns', 'badge' => $coupons->count()],

        ['group' => 'Coaching & Gym', 'tab' => 'coaching', 'label' => 'Coaching & Diet Plans', 'badge' => $coachingPackagesCount.'/'.$dietPlansCount],
        ['group' => 'Coaching & Gym', 'tab' => 'gym', 'label' => 'Gym Memberships & Hours', 'badge' => $gymMemberships->count()],

        ['group' => 'People & Support', 'tab' => 'users', 'label' => 'Users, Staff & Roles', 'badge' => $users->count()],
        ['group' => 'People & Support', 'tab' => 'assignments', 'label' => 'Coach Assignments', 'badge' => $assignments->count()],
        ['group' => 'People & Support', 'tab' => 'support', 'label' => 'Support Tickets', 'badge' => $supportTickets->count()],

        ['group' => 'Content', 'tab' => 'content', 'label' => 'CMS, Media & Testimonials', 'badge' => $cmsPages->count().'/'.$mediaAssets->count()],
        ['group' => 'Content', 'tab' => 'seo', 'label' => 'SEO & Notifications', 'badge' => $seoMetadataRecords->count()],

        ['group' => 'System', 'tab' => 'settings', 'label' => 'Website Settings', 'badge' => 'Config'],
        ['group' => 'System', 'tab' => 'audit', 'label' => 'Audit Logs, Health & 2FA', 'badge' => $systemHealth['audit_logs_count']],
    ];
    $verifiedRevenuePaisa = $financialSummary['net_reconciled_paisa'];
@endphp

<x-layouts.dashboard
    title="Admin / Owner Control Center"
    role-label="Owner & Platform Admin"
    role-badge="Admin"
    :navigation="$adminModules"
>
    {{-- MODULE 1: Reconciled Revenue Analytics, Date-Range Filter, Business-Line Breakdown & Operational Alerts --}}
    <section id="overview" aria-label="Reconciled Revenue & Platform Overview" class="space-y-4" data-tab-panel="overview" x-show="tab === 'overview'" x-cloak>
        <div class="rounded-sm border border-graphite/12 bg-white p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 1 · Reconciled Financial &amp; Operational Intelligence</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Verified Revenue Analytics &amp; Business-Line Reconciliation</h2>
                    <p class="text-xs text-graphite/70">
                        Calculated strictly from verified payments ({{ $financialSummary['from'] }} to {{ $financialSummary['to'] }}). Pending carts and unverified bank proofs are excluded from revenue.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('manage.admin') }}" class="flex flex-wrap items-center gap-2 text-xs">
                        <input type="date" name="date_from" value="{{ $filterDateFrom }}" aria-label="Start date" class="field-input py-1 text-xs" />
                        <span class="text-graphite/60">to</span>
                        <input type="date" name="date_to" value="{{ $filterDateTo }}" aria-label="End date" class="field-input py-1 text-xs" />
                        <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 font-bold uppercase text-gold cursor-pointer">Filter Range</button>
                    </form>
                    <a
                        href="{{ route('manage.reports.orders-export', ['date_from' => $filterDateFrom, 'date_to' => $filterDateTo]) }}"
                        class="btn-light-primary rounded-xs px-3 py-1.5 text-xs"
                    >
                        Export Verified Orders CSV →
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card
                    label="Net Reconciled Revenue"
                    :value="$financialSummary['net_reconciled_formatted']"
                    :meta="$financialSummary['verified_orders_count'].' verified orders · Refunded: '.$financialSummary['refunded_formatted']"
                    surface="light"
                />
                <x-ui.stat-card
                    label="Unverified Order Pipeline"
                    :value="$financialSummary['unverified_pipeline_formatted']"
                    :meta="$financialSummary['unverified_pipeline_count'].' awaiting bank proof / COD collection (Excluded from revenue)'"
                    surface="light"
                />
                <x-ui.stat-card
                    label="Active Coaching & Gym"
                    :value="$operationalMetrics['active_coaching_subscriptions_count'].' Coaching · '.$operationalMetrics['active_gym_memberships_count'].' Gym'"
                    :meta="$financialSummary['active_entitlements_count'].' active digital entitlements'"
                    surface="light"
                />
                <x-ui.stat-card
                    label="Operational Alerts"
                    :value="$operationalMetrics['low_stock_count'].' Low Stock · '.$operationalMetrics['open_support_tickets_count'].' Tickets'"
                    :meta="$operationalMetrics['pending_payment_proofs_count'].' bank proofs awaiting verification'"
                    surface="light"
                />
            </div>

            {{-- 4 Business Lines Breakdown --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['coaching' => '1. Online Coaching', 'shop' => '2. Supplement Shop', 'diet' => '3. Digital Diet Plans', 'gym' => '4. Physical Gym'] as $lineKey => $lineLabel)
                    @php $lineStat = $financialSummary['by_business_line'][$lineKey] ?? ['orders_count' => 0, 'net_formatted' => 'PKR 0', 'gross_paisa' => 0, 'refunded_paisa' => 0]; @endphp
                    <div class="rounded-xs border border-graphite/12 bg-paper p-3.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-bold uppercase text-gold-dark">{{ $lineLabel }}</span>
                            <span class="font-mono text-[11px] text-graphite/70">{{ $lineStat['orders_count'] }} Verified</span>
                        </div>
                        <p class="mt-1 font-display text-2xl text-graphite">{{ $lineStat['net_formatted'] }}</p>
                        <p class="text-[11px] text-graphite/65">
                            Gross: {{ Money::format($lineStat['gross_paisa']) }} · Refunded: {{ Money::format($lineStat['refunded_paisa']) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- MODULE 6 & 7: Orders, Fulfillment, Payment Verification & Admin Refunds --}}
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
                <x-ui.badge tone="gold">{{ $orders->count() }} Orders</x-ui.badge>
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
                                <a href="{{ route('orders.confirmation', ['orderNumber' => $ord->order_number, 'token' => $ord->access_token]) }}" class="font-mono font-bold text-graphite hover:text-gold-dark">
                                    {{ $ord->order_number }}
                                </a>
                                <span class="block text-graphite/70">{{ $ord->customer_name }} ({{ $ord->customer_phone }})</span>
                                <a href="{{ route('orders.invoice', ['orderNumber' => $ord->order_number, 'token' => $ord->access_token]) }}" class="text-[11px] font-semibold text-gold-dark hover:underline">
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
    </section>

    {{-- MODULE 4 & 5: Inventory Batches, Stock Adjustments & New Product Creation --}}
    <div id="inventory-batches" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="inventory" x-show="tab === 'inventory'" x-cloak>
        <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl uppercase text-graphite">Inventory Batches, Expiry & Stock Control</h2>
                <x-ui.badge tone="gold">Atomic FEFO</x-ui.badge>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-graphite/15 text-gold-dark uppercase">
                            <th class="py-2 pr-2">SKU & Variant</th>
                            <th class="py-2 px-2">On Hand / Reserved</th>
                            <th class="py-2 px-2">Receive Batch</th>
                            <th class="py-2 pl-2">Adjust Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite/10">
                        @foreach ($variants as $v)
                            <tr>
                                <td class="py-2.5 pr-2">
                                    <strong class="font-mono text-graphite block">{{ $v->sku }}</strong>
                                    <span class="text-[11px] text-graphite/65">{{ $v->product?->name }} ({{ $v->label() }})</span>
                                </td>
                                <td class="py-2.5 px-2">
                                    <span class="font-bold text-success">{{ $v->availableStock() }} avail</span>
                                    <span class="block text-[11px] text-graphite/60">On Hand: {{ $v->stock_on_hand }} · Res: {{ $v->stock_reserved }}</span>
                                </td>
                                <td class="py-2.5 px-2">
                                    <form method="POST" action="{{ route('manage.variants.batches.store', $v) }}" class="flex items-center gap-1">
                                        @csrf
                                        <input type="text" name="batch_number" placeholder="LOT-2026B" required class="field-input py-1 text-[11px] w-24" />
                                        <input type="number" name="quantity_received" value="10" min="1" required class="field-input py-1 text-[11px] w-14" />
                                        <input type="date" name="expires_at" value="{{ now()->addMonths(18)->toDateString() }}" class="field-input py-1 text-[11px] w-28" />
                                        <button type="submit" class="rounded-xs bg-ink px-2 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">+Batch</button>
                                    </form>
                                </td>
                                <td class="py-2.5 pl-2">
                                    <form method="POST" action="{{ route('manage.variants.stock.adjust', $v) }}" class="flex items-center gap-1">
                                        @csrf
                                        <input type="number" name="delta" value="5" required class="field-input py-1 text-[11px] w-14" />
                                        <input type="hidden" name="reason" value="Cycle count adjustment" />
                                        <button type="submit" class="rounded-xs border border-graphite px-2 py-1 text-[10px] font-bold uppercase text-graphite cursor-pointer">Adj</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section id="catalog-admin" class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl uppercase text-graphite">Add Supplement / Gear Product</h2>
                <x-ui.badge tone="light">catalog.manage</x-ui.badge>
            </div>
            <form method="POST" action="{{ route('manage.products.store') }}" class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 text-xs">
                @csrf
                <div class="sm:col-span-2">
                    <label class="field-label text-xs">Product Name</label>
                    <input type="text" name="name" required placeholder="[DEMO] Essential Amino Acids (EAA) — 30 Servings" class="field-input text-xs" />
                </div>
                <div>
                    <label class="field-label text-xs">Category</label>
                    <select name="category_id" class="field-input text-xs" required>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label text-xs">Brand</label>
                    <select name="brand_id" class="field-input text-xs">
                        @foreach ($brands as $br)
                            <option value="{{ $br->id }}">{{ $br->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label text-xs">Product SKU</label>
                    <input type="text" name="sku" required placeholder="DEMO-EAA-30" class="field-input text-xs uppercase" />
                </div>
                <div>
                    <label class="field-label text-xs">Size & Flavor</label>
                    <input type="text" name="size" value="300 g (30 Servings)" required class="field-input text-xs" />
                </div>
                <div>
                    <label class="field-label text-xs">Retail Price (PKR)</label>
                    <input type="number" name="price_pkr" value="7200" min="1" required class="field-input text-xs" />
                </div>
                <div>
                    <label class="field-label text-xs">Initial Stock</label>
                    <input type="number" name="stock_on_hand" value="15" min="0" required class="field-input text-xs" />
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label text-xs">Regulatory Documentation Status</label>
                    <select name="regulatory_status" class="field-input text-xs">
                        <option value="pending_verification">Pending Importer/DRAP Verification</option>
                        <option value="verified">Verified Importer & Batch COA</option>
                        <option value="exempt_accessory">Exempt Gym Accessory</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">Create Catalog Product & Default Variant</x-ui.button>
                </div>
            </form>
        </section>
    </div>

    {{-- MODULE 13 & 15: Coupons, Return Requests & Review Moderation --}}
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
        </section>
    </div>

    @php
        $coachingPackages = $coachingPackages ?? collect();
        $dietPlans = $dietPlans ?? collect();
        $subscriptions = $subscriptions ?? collect();
        $intakes = $intakes ?? collect();
        $planRequests = $planRequests ?? collect();
    @endphp

    {{-- STAGE 4 MODULE 8 & 9: ADMIN COACHING PACKAGES, DIET PLANS & SUBSCRIPTION GOVERNANCE --}}
    <div id="coaching-diet-admin" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="coaching" x-show="tab === 'coaching'" x-cloak>
        <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl uppercase text-graphite">Coaching Packages & Subscriptions ({{ $subscriptions->count() }})</h2>
                <x-ui.badge tone="gold">coaching.packages.manage</x-ui.badge>
            </div>

            <form method="POST" action="{{ route('manage.coaching-packages.store') }}" class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 border-b border-graphite/10 pb-4 text-xs">
                @csrf
                <input type="text" name="name" placeholder="20-Week Prep Tier" class="field-input text-xs sm:col-span-2" required />
                <input type="number" name="duration_days" value="140" min="7" class="field-input text-xs" placeholder="Days" required />
                <input type="number" name="price_pkr" value="64000" min="1000" class="field-input text-xs" placeholder="PKR" required />
                <input type="text" name="tagline" value="High-touch strength & nutrition coaching tier" class="field-input text-xs sm:col-span-2" required />
                <input type="number" name="checkins_per_week" value="2" min="1" max="7" class="field-input text-xs" required />
                <x-ui.button type="submit" variant="graphite" size="sm">Add Package</x-ui.button>
            </form>

            <div class="space-y-2 text-xs">
                @foreach ($subscriptions as $sub)
                    <div class="flex items-center justify-between rounded-xs bg-paper px-3 py-2">
                        <div>
                            <strong class="font-mono text-graphite">{{ $sub->subscription_number }}</strong> — {{ $sub->user?->name }}
                            <span class="block text-[11px] text-graphite/70">{{ $sub->package?->name }} · Coach: {{ $sub->coach?->name ?? 'Unassigned' }}</span>
                        </div>
                        <span class="rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $sub->status }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl uppercase text-graphite">Master Diet Plans ({{ $dietPlans->count() }}) & Intakes ({{ $intakes->count() }})</h2>
                <x-ui.badge tone="gold">diet.plans.manage</x-ui.badge>
            </div>

            <form method="POST" action="{{ route('manage.diet-plans.store') }}" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 border-b border-graphite/10 pb-4 text-xs">
                @csrf
                <input type="text" name="title" placeholder="Ramadan Strength & Fat-Loss Plan" class="field-input text-xs sm:col-span-2" required />
                <select name="category" class="field-input text-xs" required>
                    @foreach (\App\Models\DietPlan::CATEGORIES as $dCat)
                        <option value="{{ $dCat }}">{{ $dCat }}</option>
                    @endforeach
                </select>
                <select name="type" class="field-input text-xs">
                    <option value="paid">Paid Digital Plan</option>
                    <option value="personalised">Personalised (Intake)</option>
                    <option value="free">Free Starter Plan</option>
                </select>
                <input type="number" name="duration_days" value="30" class="field-input text-xs" placeholder="Days" required />
                <input type="number" name="price_pkr" value="4800" class="field-input text-xs" placeholder="PKR" required />
                <input type="text" name="summary" value="Structured Suhoor & Iftar high-protein meal schedule" class="field-input text-xs sm:col-span-2" required />
                <x-ui.button type="submit" variant="graphite" size="sm">Create Diet Plan</x-ui.button>
            </form>

            <div class="space-y-2 text-xs">
                @foreach ($intakes->take(4) as $in)
                    <div class="flex items-center justify-between rounded-xs {{ $in->needs_professional_review ? 'border border-danger/40 bg-danger/5' : 'bg-paper' }} px-3 py-2">
                        <div>
                            <strong>Intake #{{ $in->id }} ({{ $in->user?->name }})</strong> — {{ $in->fitness_goal }}
                            @if ($in->review_flag_reason)
                                <span class="block text-[11px] text-danger">{{ $in->review_flag_reason }}</span>
                            @endif
                        </div>
                        <span class="font-mono text-[10px] font-bold uppercase text-gold-dark">{{ $in->review_status }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- MODULE 2 & 3: User, Staff & Role Management --}}
    <section id="users-staff" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4" data-tab-panel="users" x-show="tab === 'users'" x-cloak>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-display text-2xl uppercase text-graphite">User, Staff & Role Administration</h2>
                <p class="text-xs text-graphite/70">Admin-only role assignment & account activation/deactivation (deactivating a user immediately terminates their active sessions).</p>
            </div>
            <x-ui.badge tone="gold">UserPolicy Enforced</x-ui.badge>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-graphite/15 text-gold-dark uppercase">
                        <th class="py-2.5 pr-3">Name & Email</th>
                        <th class="py-2.5 px-3">Current Roles</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3">Update Roles (Admin Only)</th>
                        <th class="py-2.5 pl-3">Account Status / Confidential</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite/10">
                    @foreach ($users as $u)
                        <tr>
                            <td class="py-3 pr-3">
                                <span class="font-bold text-graphite block">{{ $u->name }}</span>
                                <span class="text-graphite/65">{{ $u->email }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($u->getRoleNames() as $rName)
                                        <span class="rounded-xs bg-graphite px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $rName }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-semibold uppercase {{ $u->status === 'active' ? 'text-success' : 'text-danger' }}">
                                    {{ $u->status }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                @if (auth()->id() !== $u->id)
                                    <form method="POST" action="{{ route('manage.users.roles.update', $u) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        @method('PUT')
                                        <select name="roles[]" class="field-input py-1 text-xs w-36" aria-label="Role for {{ $u->name }}">
                                            @foreach (['customer', 'coach', 'manager', 'admin'] as $roleOpt)
                                                <option value="{{ $roleOpt }}" @selected($u->hasRole($roleOpt))>{{ strtoupper($roleOpt) }}</option>
                                            @endforeach
                                        </select>
                                        <x-ui.button type="submit" variant="graphite" size="sm">Save</x-ui.button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-graphite/55 italic">Current Owner Session</span>
                                @endif
                            </td>
                            <td class="py-3 pl-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if (auth()->id() !== $u->id)
                                        <form method="POST" action="{{ route('manage.users.status.update', $u) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="{{ $u->status === 'active' ? 'deactivated' : 'active' }}" />
                                            <x-ui.button type="submit" variant="outline-dark" size="sm">
                                                {{ $u->status === 'active' ? 'Deactivate' : 'Reactivate' }}
                                            </x-ui.button>
                                        </form>
                                    @endif
                                    <a href="{{ route('manage.clients.confidential', $u) }}" class="text-xs font-semibold text-gold-dark hover:underline">
                                        Inspect File →
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- MODULE 11: Coach Assignment Model & Module 19: General Website Settings --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="assignments" x-show="tab === 'assignments'" x-cloak>
        <section id="coach-assignments" class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <h2 class="font-display text-2xl uppercase text-graphite">Coach-to-Client Assignment Model</h2>
            <p class="text-xs text-graphite/75">
                Assigns designated staff (`coach` or `manager`) to specific customers without granting full Admin privileges. Managers and Coaches can only view health/intake records of customers assigned here.
            </p>

            <form method="POST" action="{{ route('manage.coach-assignments.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4 border-y border-graphite/10 py-4">
                @csrf
                <div>
                    <label for="assign-coach" class="field-label text-xs">Staff Coach</label>
                    <select id="assign-coach" name="coach_id" class="field-input text-xs" required>
                        @foreach ($coaches as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->getRoleNames()->implode('/') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="assign-client" class="field-label text-xs">Customer</label>
                    <select id="assign-client" name="client_id" class="field-input text-xs" required>
                        @foreach ($customers as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="assign-context" class="field-label text-xs">Scope Context</label>
                    <select id="assign-context" name="context" class="field-input text-xs">
                        <option value="coaching">Online Coaching</option>
                        <option value="diet">Diet Preparation</option>
                        <option value="gym">Gym Personal Training</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">Assign Coach</x-ui.button>
                </div>
            </form>

            <div class="space-y-2 text-xs">
                <h3 class="font-bold uppercase text-gold-dark">Active Assignments ({{ $assignments->count() }})</h3>
                @foreach ($assignments as $a)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xs bg-paper px-3.5 py-2">
                        <span><strong>{{ $a->coach?->name }}</strong> → <strong>{{ $a->client?->name }}</strong> ({{ strtoupper($a->context) }})</span>
                        <span class="text-graphite/60">Since {{ $a->starts_at?->format('d M Y H:i') }}</span>
                    </div>
                @endforeach
            </div>
        </section>

    </div>

    {{-- Module 19: General Website Settings (own tab) --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="settings" x-show="tab === 'settings'" x-cloak>
        <section id="platform-settings" class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl uppercase text-graphite">Platform Facility Settings</h2>
                <x-ui.badge tone="light">Admin Only</x-ui.badge>
            </div>
            <form method="POST" action="{{ route('manage.settings.update') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label for="set-city" class="field-label text-xs">Confirmed Gym City</label>
                    <input id="set-city" type="text" name="gym_city" value="{{ $gymCity }}" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="set-address" class="field-label text-xs">Facility Street Address</label>
                    <input id="set-address" type="text" name="gym_address" value="{{ $gymAddress }}" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="set-phone" class="field-label text-xs">Official Contact Phone</label>
                    <input id="set-phone" type="text" name="gym_phone" value="{{ $gymPhone }}" class="field-input text-xs" required />
                </div>
                <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">
                    Save Platform Settings (Audited)
                </x-ui.button>
            </form>
        </section>
    </div>

    {{-- MODULE 10: Stage 5 Admin Gym Packages, Pricing, Revenue, Attendance, Opening Hours & Holidays --}}
    <div id="admin-gym-governance" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="gym" x-show="tab === 'gym'" x-cloak>
        <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 10 · Admin Gym Package &amp; Pricing Control</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Gym Membership Packages &amp; Revenue</h2>
                </div>
                <span class="rounded-xs bg-charcoal px-3 py-1 font-mono text-xs font-bold text-gold">
                    Verified Gym Revenue: {{ \App\Support\Money::format($verifiedGymRevenuePaisa) }}
                </span>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center text-xs">
                <div class="rounded-xs bg-paper p-3">
                    <span class="text-[10px] uppercase text-graphite/70 block">Active Memberships</span>
                    <strong class="font-display text-2xl text-graphite">{{ $gymMemberships->where('status', 'active')->count() }}</strong>
                </div>
                <div class="rounded-xs bg-paper p-3">
                    <span class="text-[10px] uppercase text-graphite/70 block">Recorded Visits</span>
                    <strong class="font-display text-2xl text-graphite">{{ $gymCheckIns->count() }}</strong>
                </div>
                <div class="rounded-xs bg-paper p-3">
                    <span class="text-[10px] uppercase text-graphite/70 block">Scheduled Classes</span>
                    <strong class="font-display text-2xl text-graphite">{{ $gymClasses->count() }}</strong>
                </div>
            </div>

            <form method="POST" action="{{ route('manage.gym.plans.store') }}" class="rounded-xs bg-paper p-4 space-y-2.5 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Configure New Gym Membership Package (Admin Only)</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <input type="text" name="name" placeholder="Package Name" class="field-input text-xs py-1.5 sm:col-span-2" required />
                    <input type="number" name="duration_days" value="30" placeholder="Days" class="field-input text-xs py-1.5" required />
                    <input type="number" name="price_pkr" placeholder="Price (PKR)" class="field-input text-xs py-1.5" required />
                    <input type="number" name="registration_fee_pkr" value="0" placeholder="Reg Fee (PKR)" class="field-input text-xs py-1.5" />
                    <input type="text" name="access_hours" value="All Operating Hours" class="field-input text-xs py-1.5" />
                </div>
                <input type="text" name="inclusions_csv" placeholder="Inclusions (comma-separated)..." class="field-input text-xs py-1.5" />
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs w-full">Save Gym Membership Package</button>
            </form>
        </section>

        <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Facility Timings &amp; Holiday Closures</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Configure Opening Hours &amp; Holidays</h2>
                </div>
                <x-ui.badge tone="light">settings.manage</x-ui.badge>
            </div>

            @if ($openingHours->isNotEmpty())
                @php $firstHour = $openingHours->first(); @endphp
                <form method="POST" action="{{ route('manage.gym.opening-hours.update', $firstHour) }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
                    @csrf
                    @method('PUT')
                    <strong class="uppercase text-graphite block">Update {{ $firstHour->day_name }} Operating Window</strong>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <input type="time" name="opens_at" value="{{ substr((string) $firstHour->opens_at, 0, 5) }}" class="field-input text-xs py-1" />
                        <input type="time" name="closes_at" value="{{ substr((string) $firstHour->closes_at, 0, 5) }}" class="field-input text-xs py-1" />
                        <input type="text" name="peak_window" value="{{ $firstHour->peak_window }}" placeholder="Peak window" class="field-input text-xs py-1" />
                        <button type="submit" class="rounded-xs bg-charcoal px-3 py-1 text-[11px] font-bold uppercase text-gold cursor-pointer">Save Hours</button>
                    </div>
                </form>
            @endif

            <form method="POST" action="{{ route('manage.gym.holidays.store') }}" class="rounded-xs border border-graphite/15 p-3.5 space-y-2 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Add Holiday / Maintenance Schedule</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <input type="text" name="title" placeholder="Holiday / Maintenance Title" class="field-input text-xs py-1.5 sm:col-span-2" required />
                    <input type="date" name="holiday_date" value="{{ now()->addDays(14)->toDateString() }}" class="field-input text-xs py-1.5" required />
                </div>
                <div class="flex items-center justify-between gap-2">
                    <label class="inline-flex items-center gap-1.5 text-xs">
                        <input type="checkbox" name="is_closed" value="1" checked /> Facility Closed All Day
                    </label>
                    <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs">Save Holiday Notice</button>
                </div>
            </form>
        </section>
    </div>

    {{-- STAGE 6 MODULE 17: Support Tickets & Customer Service Governance --}}
    <section id="support-tickets" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-5" data-tab-panel="support" x-show="tab === 'support'" x-cloak>
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 17 · Customer Support Desk &amp; Escalations</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Support Tickets, Internal Staff Notes &amp; SLA Status</h2>
            </div>
            <form method="GET" action="{{ route('manage.admin') }}#support-tickets" class="flex items-center gap-2 text-xs">
                <select name="ticket_status" class="field-input py-1 text-xs">
                    <option value="">All Ticket Statuses</option>
                    @foreach (['open', 'in_progress', 'awaiting_customer', 'resolved', 'closed'] as $tStatus)
                        <option value="{{ $tStatus }}" @selected($filterTicketStatus === $tStatus)>{{ strtoupper($tStatus) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xs bg-charcoal px-3 py-1 font-bold uppercase text-gold cursor-pointer">Filter</button>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($supportTickets as $ticket)
                <div class="rounded-xs border border-graphite/15 bg-paper p-4 space-y-3 text-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold text-gold-dark">{{ $ticket->ticket_number }}</span>
                            <span class="ml-1.5 rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $ticket->category }} · {{ $ticket->priority }}</span>
                            <h3 class="mt-1 font-semibold text-sm text-graphite">{{ $ticket->subject }}</h3>
                            <p class="text-[11px] text-graphite/70">
                                Customer: <strong>{{ $ticket->user?->name }}</strong>
                                @if ($ticket->order) · Order: {{ $ticket->order->order_number }} @endif
                            </p>
                        </div>
                        <x-ui.badge :tone="$ticket->status === 'resolved' ? 'success' : 'gold'">{{ $ticket->status }}</x-ui.badge>
                    </div>

                    <div class="max-h-40 overflow-y-auto space-y-1.5 rounded-xs bg-white p-2.5 border border-graphite/10">
                        @foreach ($ticket->messages as $msg)
                            <div class="rounded-xs p-2 {{ $msg->is_internal_note ? 'bg-amber-50 border border-amber-300' : 'bg-paper' }}">
                                <div class="flex items-center justify-between text-[10px] text-graphite/65">
                                    <strong>{{ $msg->sender?->name }} @if ($msg->is_internal_note) [INTERNAL STAFF NOTE] @endif</strong>
                                    <span>{{ $msg->created_at?->diffForHumans() }}</span>
                                </div>
                                <p class="mt-0.5 text-graphite">{{ $msg->message }}</p>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('manage.support-tickets.update', $ticket) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                        @csrf
                        @method('PUT')
                        <select name="status" class="field-input py-1 text-xs">
                            @foreach (['open', 'in_progress', 'awaiting_customer', 'resolved', 'closed'] as $st)
                                <option value="{{ $st }}" @selected($ticket->status === $st)>{{ strtoupper($st) }}</option>
                            @endforeach
                        </select>
                        <select name="priority" class="field-input py-1 text-xs">
                            @foreach (['low', 'normal', 'high', 'urgent'] as $pr)
                                <option value="{{ $pr }}" @selected($ticket->priority === $pr)>{{ strtoupper($pr) }}</option>
                            @endforeach
                        </select>
                        <select name="assigned_to" class="field-input py-1 text-xs">
                            <option value="">Unassigned</option>
                            @foreach ($coaches as $staffUser)
                                <option value="{{ $staffUser->id }}" @selected($ticket->assigned_to === $staffUser->id)>{{ $staffUser->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-xs bg-charcoal px-3 py-1 font-bold uppercase text-gold cursor-pointer">Update Status</button>
                    </form>

                    <form method="POST" action="{{ route('manage.support-tickets.reply', $ticket) }}" class="space-y-2 border-t border-graphite/10 pt-2">
                        @csrf
                        <input type="text" name="message" placeholder="Reply to customer or record internal staff note..." class="field-input py-1.5 text-xs" required />
                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center gap-1.5 text-[11px] text-graphite/80">
                                <input type="checkbox" name="is_internal_note" value="1" /> Internal Staff Note (Hidden from Customer)
                            </label>
                            <button type="submit" class="btn-light-primary rounded-xs px-3 py-1 text-xs">Send Reply</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </section>

    {{-- STAGE 6 MODULE 14 & 15: CMS Pages, Media Library & Consent-Verified Testimonials --}}
    <div id="cms-media-library" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="content" x-show="tab === 'content'" x-cloak>
        <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 14 · CMS Content &amp; Policy Pages</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">CMS Pages &amp; Media Library</h2>
                </div>
                <x-ui.badge tone="gold">{{ $cmsPages->count() }} Pages · {{ $mediaAssets->count() }} Assets</x-ui.badge>
            </div>

            <div class="space-y-2 text-xs max-h-48 overflow-y-auto">
                @foreach ($cmsPages as $page)
                    <div class="flex items-center justify-between rounded-xs border border-graphite/10 bg-paper p-2.5">
                        <div>
                            <strong class="text-graphite">{{ $page->title }}</strong>
                            <span class="block font-mono text-[11px] text-graphite/65">/{{ $page->slug }} · {{ strtoupper($page->section) }}</span>
                        </div>
                        <x-ui.badge :tone="$page->status === 'published' ? 'success' : 'light'">{{ $page->status }}</x-ui.badge>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('manage.cms-pages.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Publish or Update CMS Page (content.manage)</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <input type="text" name="title" placeholder="Page Title" class="field-input py-1.5 text-xs sm:col-span-2" required />
                    <select name="section" class="field-input py-1.5 text-xs">
                        <option value="policy">Policy</option>
                        <option value="editorial">Editorial Guide</option>
                        <option value="faq">FAQ</option>
                        <option value="landing">Landing Section</option>
                    </select>
                </div>
                <input type="text" name="excerpt" placeholder="Short summary..." class="field-input py-1.5 text-xs" />
                <textarea name="body" rows="2" placeholder="Page markdown or editorial content..." class="field-input py-1.5 text-xs" required></textarea>
                <div class="flex items-center justify-between">
                    <select name="status" class="field-input py-1 text-xs w-36">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                    <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs">Save CMS Page</button>
                </div>
            </form>

            <form method="POST" action="{{ route('manage.media-assets.store') }}" class="rounded-xs border border-graphite/15 p-3.5 space-y-2 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Register Brand Media Library Asset (media.manage)</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <input type="text" name="title" placeholder="Asset Title" class="field-input py-1.5 text-xs" required />
                    <select name="collection" class="field-input py-1.5 text-xs">
                        <option value="hero">Hero</option>
                        <option value="coaching">Coaching</option>
                        <option value="shop">Supplement Shop</option>
                        <option value="gym">Physical Gym</option>
                    </select>
                    <input type="text" name="path" placeholder="/images/demo/..." class="field-input py-1.5 text-xs" required />
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" name="alt_text" placeholder="WCAG 2.2 AA Accessible Alt Text..." class="field-input py-1.5 text-xs flex-1" required />
                    <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 font-bold uppercase text-gold cursor-pointer">Save Asset</button>
                </div>
            </form>
        </section>

        <section id="testimonials-governance" class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 15 · Consent-Backed Testimonials &amp; Privacy Requests</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Testimonials &amp; Privacy Governance</h2>
                </div>
                <x-ui.badge tone="gold">{{ $testimonials->count() }} Testimonials</x-ui.badge>
            </div>

            <div class="space-y-2 text-xs">
                @foreach ($testimonials as $testi)
                    <div class="rounded-xs border border-graphite/10 bg-paper p-3">
                        <div class="flex items-center justify-between">
                            <strong>{{ $testi->author_label }} · {{ $testi->program_context }}</strong>
                            <x-ui.badge :tone="$testi->status === 'published' ? 'success' : 'light'">
                                {{ $testi->status }} (Consent #{{ $testi->consent_record_id ?? 'None' }})
                            </x-ui.badge>
                        </div>
                        <p class="mt-1 text-graphite/80 italic">"{{ $testi->quote }}"</p>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('manage.testimonials.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Add Consent-Verified Testimonial (testimonials.manage)</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <input type="text" name="author_label" placeholder="Client Label (e.g. Verified Coaching Client)" class="field-input py-1.5 text-xs" required />
                    <input type="text" name="program_context" placeholder="Program (e.g. 12-Week Transformation)" class="field-input py-1.5 text-xs" required />
                </div>
                <textarea name="quote" rows="2" placeholder="Verified client quote..." class="field-input py-1.5 text-xs" required></textarea>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                    <select name="consent_record_id" class="field-input py-1 text-xs sm:col-span-2">
                        <option value="">Select Active Consent Record...</option>
                        @foreach ($consentRecords as $cr)
                            <option value="{{ $cr->id }}">Consent #{{ $cr->id }} — {{ $cr->user?->name }} ({{ $cr->consent_type }})</option>
                        @endforeach
                    </select>
                    <input type="number" name="rating" value="5" min="1" max="5" class="field-input py-1 text-xs" required />
                    <select name="status" class="field-input py-1 text-xs">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs w-full">Save Testimonial</button>
            </form>

            {{-- Customer Privacy & Data Export Requests --}}
            <div class="border-t border-graphite/10 pt-3 space-y-2 text-xs">
                <strong class="uppercase text-graphite block">Customer Privacy &amp; Data Export Requests ({{ $privacyRequests->count() }})</strong>
                @foreach ($privacyRequests as $prReq)
                    <div class="rounded-xs border border-graphite/12 p-2.5 flex flex-wrap items-center justify-between gap-2">
                        <form method="POST" action="{{ route('manage.privacy-requests.resolve', $prReq) }}" class="flex flex-1 flex-wrap items-center justify-between gap-2">
                            @csrf
                            @method('PUT')
                            <div>
                                <strong>{{ $prReq->user?->name }}</strong> · <span class="font-mono text-gold-dark">{{ $prReq->request_type }}</span>
                                <span class="block text-[11px] text-graphite/70">{{ $prReq->customer_notes }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <select name="status" class="field-input py-1 text-xs">
                                    @foreach (['pending', 'in_review', 'completed', 'rejected'] as $pst)
                                        <option value="{{ $pst }}" @selected($prReq->status === $pst)>{{ strtoupper($pst) }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="resolution_notes" value="{{ $prReq->resolution_notes ?? 'Processed per data governance policy.' }}" class="field-input py-1 text-xs w-44" required />
                                <button type="submit" class="rounded-xs bg-charcoal px-2.5 py-1 text-[11px] font-bold uppercase text-gold cursor-pointer">Resolve</button>
                            </div>
                        </form>
                        @if (in_array($prReq->request_type, [\App\Models\PrivacyRequest::TYPE_ACCOUNT_DELETION_ENQUIRY, 'account_deletion'], true) && $prReq->status !== 'completed')
                            <form method="POST" action="{{ route('manage.privacy-requests.anonymise', $prReq) }}">
                                @csrf
                                <button type="submit" class="rounded-xs bg-danger px-2.5 py-1 text-[11px] font-bold uppercase text-ivory cursor-pointer">
                                    Anonymise PII
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- STAGE 6 MODULE 16 & 18: SEO Settings & Notification Templates --}}
    <div id="seo-settings" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="seo" x-show="tab === 'seo'" x-cloak>
        <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 16 · Technical SEO &amp; Canonical Metadata</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">SEO Metadata Configuration</h2>
                </div>
                <x-ui.badge tone="light">seo.manage</x-ui.badge>
            </div>

            <div class="space-y-2 text-xs">
                @foreach ($seoMetadataRecords as $seo)
                    <div class="rounded-xs bg-paper p-2.5 border border-graphite/10">
                        <strong class="block text-graphite">{{ $seo->meta_title }}</strong>
                        <span class="text-[11px] text-graphite/70 block">{{ $seo->meta_description }}</span>
                        <span class="font-mono text-[10px] text-gold-dark">{{ $seo->canonical_url }} ({{ $seo->robots }})</span>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('manage.seo-metadata.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Save Entity SEO Metadata</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <select name="seoable_type" class="field-input py-1.5 text-xs">
                        <option value="App\Models\Product">Product</option>
                        <option value="App\Models\CoachingPackage">Coaching Package</option>
                        <option value="App\Models\DietPlan">Diet Plan</option>
                        <option value="App\Models\CmsPage">CMS Page</option>
                    </select>
                    <input type="number" name="seoable_id" value="1" min="1" class="field-input py-1.5 text-xs" required />
                    <select name="robots" class="field-input py-1.5 text-xs">
                        <option value="index,follow">index,follow</option>
                        <option value="noindex,nofollow">noindex,nofollow</option>
                    </select>
                </div>
                <input type="text" name="meta_title" placeholder="Meta Title (<= 160 chars)" class="field-input py-1.5 text-xs" required />
                <input type="text" name="meta_description" placeholder="Meta Description (<= 320 chars)" class="field-input py-1.5 text-xs" required />
                <input type="text" name="canonical_url" placeholder="https://binmalikfitness.com/..." class="field-input py-1.5 text-xs" />
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs w-full">Save SEO Metadata</button>
            </form>
        </section>

        <section id="notification-templates" class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 18 · Transactional &amp; Operational Templates</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Notification Templates</h2>
                </div>
                <x-ui.badge tone="gold">{{ $notificationTemplates->count() }} Templates</x-ui.badge>
            </div>

            <div class="space-y-2 text-xs max-h-48 overflow-y-auto">
                @foreach ($notificationTemplates as $tpl)
                    <div class="rounded-xs bg-paper p-2.5 border border-graphite/10">
                        <div class="flex items-center justify-between">
                            <strong class="font-mono text-gold-dark">{{ $tpl->key }}</strong>
                            <span class="uppercase text-[10px] font-bold text-graphite/70">{{ $tpl->channel }}</span>
                        </div>
                        <p class="font-semibold text-graphite">{{ $tpl->name }}</p>
                        <p class="text-[11px] text-graphite/70">{{ $tpl->body_template }}</p>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('manage.notification-templates.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Save Notification Template (notifications.manage)</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <input type="text" name="key" placeholder="event.key (e.g. order.shipped)" class="field-input py-1.5 text-xs" required />
                    <input type="text" name="name" placeholder="Template Name" class="field-input py-1.5 text-xs" required />
                    <select name="channel" class="field-input py-1.5 text-xs">
                        <option value="database">Database In-App</option>
                        <option value="email">Transactional Email</option>
                        <option value="whatsapp_consent">WhatsApp (Consent)</option>
                    </select>
                </div>
                <input type="text" name="subject" placeholder="Email/Alert Subject..." class="field-input py-1.5 text-xs" />
                <textarea name="body_template" rows="2" placeholder="Message body with @{{placeholders}}..." class="field-input py-1.5 text-xs" required></textarea>
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs w-full">Save Notification Template</button>
            </form>
        </section>
    </div>

    {{-- Optional 2FA for Privileged Admin --}}
    <div data-tab-panel="audit" x-show="tab === 'audit'" x-cloak>
        <x-auth.two-factor-panel />
    </div>

    {{-- MODULE 20: System Operational Health Monitor & Immutable Audit Logs --}}
    <section id="audit-logs" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-5" data-tab-panel="audit" x-show="tab === 'audit'" x-cloak>
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 20 · System Health, Queue/Storage Monitor &amp; Audit Trail</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Operational Health &amp; Security Audit Logs</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('manage.security.prune-tokens') }}">
                    @csrf
                    <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 text-xs font-bold uppercase text-gold cursor-pointer">
                        Prune Expired Tokens &amp; Guest Carts
                    </button>
                </form>
                <x-ui.badge tone="success">
                    DB: {{ $systemHealth['db_connected'] ? 'Connected' : 'Error' }} · Private Disk: {{ $systemHealth['private_disk_writable'] ? 'Writable' : 'Check Perms' }}
                </x-ui.badge>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 text-xs">
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/65 block">Runtime Stack</span>
                <strong class="text-graphite">PHP {{ $systemHealth['php_version'] }} / Laravel {{ $systemHealth['laravel_version'] }}</strong>
            </div>
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/65 block">Encrypted Private Files</span>
                <strong class="text-graphite">{{ $systemHealth['private_files_count'] }} Protected Files</strong>
            </div>
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/65 block">Queue Health ({{ $systemHealth['queue_driver'] }})</span>
                <strong class="text-graphite">{{ $systemHealth['pending_jobs_count'] }} Pending · {{ $systemHealth['failed_jobs_count'] }} Failed</strong>
            </div>
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/65 block">Recorded Audit Events</span>
                <strong class="text-graphite">{{ $systemHealth['audit_logs_count'] }} Immutable Logs</strong>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-graphite/12 text-gold-dark uppercase">
                        <th class="py-2 pr-3">Timestamp</th>
                        <th class="py-2 px-3">Actor</th>
                        <th class="py-2 px-3">Action</th>
                        <th class="py-2 px-3">Subject</th>
                        <th class="py-2 pl-3">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite/10">
                    @foreach ($auditLogs as $log)
                        <tr>
                            <td class="py-2 pr-3 font-mono text-[11px]">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="py-2 px-3 font-semibold">{{ $log->actor?->name ?? 'System / Guest' }}</td>
                            <td class="py-2 px-3 font-mono text-gold-dark">{{ $log->action }}</td>
                            <td class="py-2 px-3 text-graphite/70">{{ class_basename((string) $log->subject_type) }} #{{ $log->subject_id }}</td>
                            <td class="py-2 pl-3 font-mono text-[11px] text-graphite/60">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.dashboard>
