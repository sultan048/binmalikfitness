{{-- Tab panel "overview" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
@php
    use App\Support\Money;
@endphp
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
