@php
    use App\Support\Money;
    $supportTickets = $supportTickets ?? collect();
    $roleNotifications = $roleNotifications ?? collect();
    $consentRecords = $consentRecords ?? collect();
    $privacyRequests = $privacyRequests ?? collect();
    // Each 'tab' key matches a data-tab-panel below; 'group' is the sidebar category heading.
    $customerModules = [
        ['group' => 'Home', 'tab' => 'overview', 'label' => 'Overview & Today', 'badge' => $roleNotifications->whereNull('read_at')->count().' Alerts'],

        ['group' => 'Online Coaching', 'tab' => 'coaching', 'label' => 'Subscription, Diet & Videos', 'badge' => $entitlements->count()],
        ['group' => 'Online Coaching', 'tab' => 'workouts', 'label' => 'Workouts & Intake', 'badge' => $workoutAssignments->count()],
        ['group' => 'Online Coaching', 'tab' => 'progress', 'label' => 'Check-Ins & Progress', 'badge' => $checkIns->count()],
        ['group' => 'Online Coaching', 'tab' => 'sessions', 'label' => 'Consultations & Messages', 'badge' => $consultations->count()],

        ['group' => 'Gym', 'tab' => 'gym', 'label' => 'Membership & Classes', 'badge' => $gymMembership ? strtoupper($gymMembership->status) : 'Join'],

        ['group' => 'Shop', 'tab' => 'orders', 'label' => 'Orders, Invoices & Wishlist', 'badge' => $orders->count()],

        ['group' => 'My Account', 'tab' => 'profile', 'label' => 'Profile & Files', 'badge' => $user->privateFiles->count()],
        ['group' => 'My Account', 'tab' => 'addresses', 'label' => 'Addresses & Password', 'badge' => $user->addresses->count()],
        ['group' => 'My Account', 'tab' => 'support', 'label' => 'Support & Privacy', 'badge' => $supportTickets->count().'/'.$consentRecords->count()],
    ];
    $profile = $user->customerProfile;
@endphp

<x-layouts.dashboard
    title="Member Coaching & Account Portal"
    role-label="Customer Account & Entitlements"
    role-badge="Customer"
    :navigation="$customerModules"
>
    @include('account.tabs.overview')

    @php
        $activeSubscription = $activeSubscription ?? null;
        $latestIntake = $latestIntake ?? null;
        $planRequests = $planRequests ?? collect();
        $workoutAssignments = $workoutAssignments ?? collect();
        $checkIns = $checkIns ?? collect();
        $consultations = $consultations ?? collect();
        $coachingMessages = $coachingMessages ?? collect();
        $entitlements = $entitlements ?? collect();
        $videos = $videos ?? collect();
        $entitledVideoIds = $entitledVideoIds ?? [];
    @endphp

    {{-- STAGE 4: ONLINE COACHING SUBSCRIPTION, INTAKE, DIET, WORKOUTS, CHECK-INS & PROGRESS HUB --}}
    <div id="coaching-hub" class="space-y-6">
        {{-- Subscription & Entitlements Banner --}}
        @include('account.tabs.coaching')

        {{-- Assigned Workout Program & Session Completion Logger + 12-Point Intake Form --}}
        @include('account.tabs.workouts')

        {{-- Module 9 & 10: Weekly Check-In, Body Measurements & Visual Progress Tracker --}}
        @include('account.tabs.progress')

        {{-- Module 11: Consultation Bookings & Secure Customer-Coach Messaging --}}
        @include('account.tabs.sessions')
    </div>

    {{-- MODULE 11 & 12: Stage 5 Physical Gym Membership Card, Signed QR Pass, Attendance History & Class Bookings --}}
    @include('account.tabs.gym')

    {{-- MODULE 3, 14 & 15: My Supplement Orders, Invoices & Wishlist --}}
    @include('account.tabs.orders')

    {{-- MODULE 2: My Profile & Training Parameters --}}
    @include('account.tabs.profile')

    {{-- MODULE 4: Saved Addresses & MODULE 16: Password Security --}}
    @include('account.tabs.addresses')

    {{-- STAGE 6 MODULE 15 & 16: Customer Support Tickets & Privacy / Consent Governance --}}
    @include('account.tabs.support')
</x-layouts.dashboard>
