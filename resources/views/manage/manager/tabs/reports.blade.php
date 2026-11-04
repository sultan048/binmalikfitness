{{-- Tab panel "reports" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="reports" x-show="tab === 'reports'" x-cloak>
    <section id="operational-reports" class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 11 &amp; 12 · Approved Operational Reports &amp; Coupons</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Operational KPIs &amp; Approved Promotions</h2>
            </div>
            <x-ui.badge tone="light">reports.operational</x-ui.badge>
        </div>

        <div class="grid grid-cols-2 gap-2.5 text-xs">
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/65 block">Active Subscriptions</span>
                <strong class="font-display text-2xl text-graphite">{{ $operationalMetrics['active_coaching_subscriptions_count'] }}</strong>
            </div>
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/65 block">Active Gym Members</span>
                <strong class="font-display text-2xl text-graphite">{{ $operationalMetrics['active_gym_memberships_count'] }}</strong>
            </div>
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/65 block">Conversion Verification Rate</span>
                <strong class="font-display text-2xl text-graphite">{{ $operationalMetrics['conversion_summary']['verification_rate_percent'] }}%</strong>
            </div>
            <div class="rounded-xs bg-paper p-3">
                <span class="text-[10px] uppercase text-graphite/65 block">Low Stock Alerts</span>
                <strong class="font-display text-2xl text-graphite">{{ $operationalMetrics['low_stock_count'] }} SKUs</strong>
            </div>
        </div>

        @can('coupons.manage')
            <form id="approved-promotions" method="POST" action="{{ route('manage.coupons.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Create Approved Promotional Coupon (Manager Capped ≤20% / PKR 3,000)</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <input type="text" name="code" placeholder="PROMO15" class="field-input py-1 text-xs" required />
                    <select name="discount_type" class="field-input py-1 text-xs">
                        <option value="percentage">Percentage (≤20%)</option>
                        <option value="fixed_amount">Fixed PKR (≤3,000)</option>
                    </select>
                    <input type="number" name="discount_value" value="15" min="1" class="field-input py-1 text-xs" required />
                </div>
                <button type="submit" class="btn-light-primary rounded-xs px-3 py-1.5 text-xs w-full">Save Approved Coupon</button>
            </form>
        @endcan
    </section>
</div>
