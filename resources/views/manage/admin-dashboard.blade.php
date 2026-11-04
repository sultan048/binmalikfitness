@php
    use App\Support\Money;
    // Each 'tab' key matches a data-tab-panel below; 'group' is the sidebar category heading.
    $adminModules = [
        ['group' => 'Home', 'tab' => 'overview', 'label' => 'Overview & Analytics', 'badge' => $financialSummary['net_reconciled_formatted']],

        ['group' => 'Shop & Sales', 'tab' => 'orders', 'label' => 'Orders, Payments & Refunds', 'badge' => $orders->total()],
        ['group' => 'Shop & Sales', 'tab' => 'inventory', 'label' => 'Inventory & Products', 'badge' => $productsCount.'/'.$variants->count()],
        ['group' => 'Shop & Sales', 'tab' => 'promos', 'label' => 'Coupons, Reviews & Returns', 'badge' => $coupons->count()],

        ['group' => 'Coaching & Gym', 'tab' => 'coaching', 'label' => 'Coaching & Diet Plans', 'badge' => $coachingPackagesCount.'/'.$dietPlansCount],
        ['group' => 'Coaching & Gym', 'tab' => 'gym', 'label' => 'Gym Memberships & Hours', 'badge' => $gymMembershipsCount],

        ['group' => 'People & Support', 'tab' => 'users', 'label' => 'Users, Staff & Roles', 'badge' => $users->total()],
        ['group' => 'People & Support', 'tab' => 'assignments', 'label' => 'Coach Assignments', 'badge' => ($pendingAssignments = $assignments->where('status', 'pending')->count()) ? $pendingAssignments.' Pending' : $assignments->count()],
        ['group' => 'People & Support', 'tab' => 'support', 'label' => 'Support Tickets', 'badge' => $supportTickets->total()],

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
    @include('manage.admin.tabs.overview')

    {{-- MODULE 6 & 7: Orders, Fulfillment, Payment Verification & Admin Refunds --}}
    @include('manage.admin.tabs.orders')

    {{-- MODULE 4 & 5: Inventory Batches, Stock Adjustments & New Product Creation --}}
    @include('manage.admin.tabs.inventory')

    {{-- MODULE 13 & 15: Coupons, Return Requests & Review Moderation --}}
    @include('manage.admin.tabs.promos')

    @php
        $coachingPackages = $coachingPackages ?? collect();
        $dietPlans = $dietPlans ?? collect();
        $subscriptions = $subscriptions ?? collect();
        $intakes = $intakes ?? collect();
        $planRequests = $planRequests ?? collect();
    @endphp

    {{-- STAGE 4 MODULE 8 & 9: ADMIN COACHING PACKAGES, DIET PLANS & SUBSCRIPTION GOVERNANCE --}}
    @include('manage.admin.tabs.coaching')

    {{-- MODULE 2 & 3: User, Staff & Role Management --}}
    @include('manage.admin.tabs.users')

    {{-- MODULE 11: Coach Assignment Model & Module 19: General Website Settings --}}
    @include('manage.admin.tabs.assignments')

    {{-- Module 19: General Website Settings (own tab) --}}
    @include('manage.admin.tabs.settings')

    {{-- MODULE 10: Stage 5 Admin Gym Packages, Pricing, Revenue, Attendance, Opening Hours & Holidays --}}
    @include('manage.admin.tabs.gym')

    {{-- STAGE 6 MODULE 17: Support Tickets & Customer Service Governance --}}
    @include('manage.admin.tabs.support')

    {{-- STAGE 6 MODULE 14 & 15: CMS Pages, Media Library & Consent-Verified Testimonials --}}
    @include('manage.admin.tabs.content')

    {{-- STAGE 6 MODULE 16 & 18: SEO Settings & Notification Templates --}}
    @include('manage.admin.tabs.seo')

    {{-- Optional 2FA for Privileged Admin --}}
    @include('manage.admin.tabs.audit-two-factor')

    {{-- MODULE 20: System Operational Health Monitor & Immutable Audit Logs --}}
    @include('manage.admin.tabs.audit-logs')
</x-layouts.dashboard>
