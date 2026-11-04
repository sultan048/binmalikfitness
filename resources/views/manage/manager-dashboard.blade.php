@php
    use App\Support\Money;
    // Each 'tab' key matches a data-tab-panel below; 'group' is the sidebar category heading.
    // The orders panel is only rendered for users with orders.fulfil, so its tab is dropped for everyone else.
    $managerModules = array_values(array_filter([
        ['group' => 'Home', 'tab' => 'overview', 'label' => 'Operational Overview', 'badge' => 'Live'],

        ['group' => 'Operations', 'tab' => 'orders', 'label' => 'Orders & Fulfillment', 'badge' => $orders->total(), 'visible' => $actor->can('orders.fulfil')],
        ['group' => 'Operations', 'tab' => 'inventory', 'label' => 'Inventory Actions', 'badge' => 'Audited'],

        ['group' => 'Coaching', 'tab' => 'clients', 'label' => 'Assigned Clients', 'badge' => $assignedClients->count()],
        ['group' => 'Coaching', 'tab' => 'delivery', 'label' => 'Diet, Check-Ins & Messages', 'badge' => $assignedPlanRequests->count().'/'.$assignedMessages->count()],

        ['group' => 'Gym', 'tab' => 'gym', 'label' => 'Reception & Classes', 'badge' => $gymMembershipsCount],

        ['group' => 'Support & Reports', 'tab' => 'support', 'label' => 'Support Tickets', 'badge' => $supportTickets->total()],
        ['group' => 'Support & Reports', 'tab' => 'reports', 'label' => 'Reports & Promotions', 'badge' => 'Capped ≤20%'],

        ['group' => 'Account', 'tab' => 'security', 'label' => 'Security (2FA)', 'badge' => ''],
    ], fn ($module) => $module['visible'] ?? true));
@endphp

<x-layouts.dashboard
    title="Manager & Coach Operations Workspace"
    role-label="Scoped Operations & Coaching"
    :role-badge="$actor->hasRole('manager') ? 'Manager' : 'Coach'"
    :navigation="$managerModules"
>
    {{-- MODULE 1: Today's Operational Overview & Alerts --}}
    @include('manage.manager.tabs.overview')

    {{-- MODULE 2 & 3: Manager Order Fulfillment & Payment Verification Queue --}}
    @can('orders.fulfil')
        @include('manage.manager.tabs.orders')
    @endcan

    {{-- MODULE 5 & 8: Assigned Coaching & Diet Clients (Scoped by coach_assignments) --}}
    @include('manage.manager.tabs.clients')

    @php
        $assignedIntakes = $assignedIntakes ?? collect();
        $assignedPlanRequests = $assignedPlanRequests ?? collect();
        $assignedCheckIns = $assignedCheckIns ?? collect();
        $assignedConsultations = $assignedConsultations ?? collect();
        $assignedMessages = $assignedMessages ?? collect();
        $workoutPrograms = $workoutPrograms ?? collect();
    @endphp

    {{-- STAGE 4: SCOPED COACHING & DIET DELIVERY WORKFLOW FOR ASSIGNED CLIENTS --}}
    @include('manage.manager.tabs.delivery')

    {{-- MODULE 6 & 7: Stage 5 Physical Gym Reception, QR Check-In Desk, Counter Registration, Classes & Enquiries --}}
    @include('manage.manager.tabs.gym')

    @include('manage.manager.tabs.inventory')

    {{-- STAGE 6 MODULE 10, 11 & 12: Manager Support Tickets, Limited Operational Reports & Approved Promotions --}}
    @include('manage.manager.tabs.support')

    @include('manage.manager.tabs.reports')

    @include('manage.manager.tabs.security')
</x-layouts.dashboard>
