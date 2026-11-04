{{-- Tab panel "overview" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
<section id="ops-overview" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" data-tab-panel="overview" x-show="tab === 'overview'" x-cloak>
    <x-ui.stat-card
        label="Assigned Coaching Clients"
        :value="$assignedClients->count().' Clients'"
        meta="Strictly scoped via coach_assignments"
    />
    <x-ui.stat-card
        label="Pending Orders & Proofs"
        :value="$operationalMetrics['pending_orders_count'].' Orders · '.$operationalMetrics['pending_payment_proofs_count'].' Proofs'"
        meta="Verification required before shipping"
    />
    <x-ui.stat-card
        label="Low Stock & Expiring Plans"
        :value="$operationalMetrics['low_stock_count'].' Low SKUs · '.($operationalMetrics['expiring_subscriptions']->count() + $operationalMetrics['expiring_gym_memberships']->count()).' Expiring'"
        meta="14-day renewal window monitored"
    />
    <x-ui.stat-card
        label="Support Queue & Gym Visits"
        :value="$supportTickets->total().' Tickets · '.$operationalMetrics['today_gym_checkins_count'].' Visits Today'"
        meta="Scoped by role & client assignment"
    />
</section>
