{{-- Tab panel "inventory" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="inventory" x-show="tab === 'inventory'" x-cloak>
    {{-- Authorized Inventory & Customer Account Support --}}
    <section id="inventory-panel" class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <h2 class="font-display text-2xl uppercase text-graphite">Authorized Inventory & Lot Status</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-graphite/12 text-gold-dark uppercase">
                        <th class="py-2 pr-3">Product</th>
                        <th class="py-2 px-3">Default SKU</th>
                        <th class="py-2 px-3">Available</th>
                        <th class="py-2 pl-3">Sample Lot / Expiry</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite/10">
                    @foreach ($products as $p)
                        @php $v = $p->defaultVariant; @endphp
                        <tr>
                            <td class="py-2.5 pr-3 font-semibold text-graphite">{{ $p->name }}</td>
                            <td class="py-2.5 px-3 font-mono">{{ $v?->sku }}</td>
                            <td class="py-2.5 px-3 font-semibold text-success">{{ $v?->availableStock() }}</td>
                            <td class="py-2.5 pl-3 text-graphite/70">{{ $v?->sample_lot_number }} · {{ $v?->sample_expiry_date?->format('M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Explicit Backend Restrictions Card --}}
    <section class="lg:col-span-5 rounded-sm border border-danger/30 bg-white p-6 space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-danger">Enforced Backend Restrictions</h2>
            <x-ui.badge tone="danger">403 Policy Guard</x-ui.badge>
        </div>
        <ul class="space-y-1.5 text-xs text-graphite">
            <li>✕ Cannot change Owner/Admin privileges or self-assign roles (<code class="font-mono">PUT /manage/users/*/roles</code> → 403)</li>
            <li>✕ Cannot access secret payment credentials or modify system settings (<code class="font-mono">PUT /manage/settings</code> → 403)</li>
            <li>✕ Cannot view confidential health/intake records of unassigned customers (<code class="font-mono">UserPolicy::viewConfidential</code> → 403)</li>
            <li>✕ Cannot deactivate Admin or fellow Staff accounts (<code class="font-mono">UserPolicy::toggleStatus</code> → 403)</li>
        </ul>
    </section>
</div>
