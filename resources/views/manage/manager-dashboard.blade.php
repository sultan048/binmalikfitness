@php
    use App\Support\Money;
    // Each 'tab' key matches a data-tab-panel below; 'group' is the sidebar category heading.
    // The orders panel is only rendered for users with orders.fulfil, so its tab is dropped for everyone else.
    $managerModules = array_values(array_filter([
        ['group' => 'Home', 'tab' => 'overview', 'label' => 'Operational Overview', 'badge' => 'Live'],

        ['group' => 'Operations', 'tab' => 'orders', 'label' => 'Orders & Fulfillment', 'badge' => $orders->count(), 'visible' => $actor->can('orders.fulfil')],
        ['group' => 'Operations', 'tab' => 'inventory', 'label' => 'Inventory Actions', 'badge' => 'Audited'],

        ['group' => 'Coaching', 'tab' => 'clients', 'label' => 'Assigned Clients', 'badge' => $assignedClients->count()],
        ['group' => 'Coaching', 'tab' => 'delivery', 'label' => 'Diet, Check-Ins & Messages', 'badge' => $assignedPlanRequests->count().'/'.$assignedMessages->count()],

        ['group' => 'Gym', 'tab' => 'gym', 'label' => 'Reception & Classes', 'badge' => $gymMemberships->count()],

        ['group' => 'Support & Reports', 'tab' => 'support', 'label' => 'Support Tickets', 'badge' => $supportTickets->count()],
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
            :value="$supportTickets->count().' Tickets · '.$operationalMetrics['today_gym_checkins_count'].' Visits Today'"
            meta="Scoped by role & client assignment"
        />
    </section>

    {{-- MODULE 2 & 3: Manager Order Fulfillment & Payment Verification Queue --}}
    @can('orders.fulfil')
        <section id="manager-orders" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4" data-tab-panel="orders" x-show="tab === 'orders'" x-cloak>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-display text-2xl uppercase text-graphite">Order Fulfillment & Payment Verification Queue</h2>
                    <p class="text-xs text-graphite/70">
                        Managers can verify manual bank transfer receipts and dispatch orders with tracking numbers, but cannot issue refunds (<code class="font-mono">payments.refund</code> is Admin-only).
                    </p>
                </div>
                <x-ui.badge tone="gold">{{ $orders->count() }} Orders</x-ui.badge>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-graphite/12 text-gold-dark uppercase">
                            <th class="py-2 pr-3">Order #</th>
                            <th class="py-2 px-3">Customer</th>
                            <th class="py-2 px-3">Total & Payment</th>
                            <th class="py-2 pl-3">Dispatch & Status Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite/10">
                        @foreach ($orders as $ord)
                            @php $pay = $ord->payments->first(); @endphp
                            <tr>
                                <td class="py-2.5 pr-3 font-mono font-bold text-graphite">{{ $ord->order_number }}</td>
                                <td class="py-2.5 px-3">{{ $ord->customer_name }} ({{ $ord->customer_phone }})</td>
                                <td class="py-2.5 px-3">
                                    <strong>{{ Money::format($ord->total_paisa) }}</strong> · {{ strtoupper($ord->payment_status) }}
                                    @if ($pay && $pay->status !== 'verified')
                                        <form method="POST" action="{{ route('manage.payments.verify', $pay) }}" class="inline ml-2">
                                            @csrf
                                            <button type="submit" class="rounded-xs bg-ink px-2 py-0.5 text-[10px] font-bold uppercase text-gold cursor-pointer">Verify</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="py-2.5 pl-3">
                                    <form method="POST" action="{{ route('manage.orders.status.update', $ord) }}" class="flex items-center gap-1">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="field-input py-1 text-[11px] w-28">
                                            <option value="processing" @selected($ord->status === 'processing')>Processing</option>
                                            <option value="shipped" @selected($ord->status === 'shipped')>Shipped</option>
                                            <option value="delivered" @selected($ord->status === 'delivered')>Delivered</option>
                                        </select>
                                        <input type="text" name="tracking_number" value="{{ $ord->tracking_number }}" placeholder="Tracking #" class="field-input py-1 text-[11px] w-28" />
                                        <button type="submit" class="rounded-xs border border-graphite px-2 py-1 text-[10px] font-bold uppercase text-graphite cursor-pointer">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

    {{-- MODULE 5 & 8: Assigned Coaching & Diet Clients (Scoped by coach_assignments) --}}
    <section id="assigned-clients" class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4" data-tab-panel="clients" x-show="tab === 'clients'" x-cloak>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-display text-2xl uppercase text-graphite">My Assigned Coaching & Nutrition Clients</h2>
                <p class="text-xs text-graphite/70">
                    You can only access confidential intake profiles and private progress files for customers explicitly assigned to you in <code class="font-mono">coach_assignments</code>.
                </p>
            </div>
            <x-ui.badge tone="gold">Assignment Scoped</x-ui.badge>
        </div>

        @if ($assignedClients->isEmpty())
            <x-ui.empty-state
                title="No Coaching Clients Currently Assigned"
                description="When an Admin or Manager assigns a coaching or personalised diet client to your staff profile, their intake and check-in records appear here."
                surface="light"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-graphite/12 text-gold-dark uppercase">
                            <th class="py-2 pr-3">Client Name</th>
                            <th class="py-2 px-3">Goal & Experience</th>
                            <th class="py-2 px-3">Private Uploads</th>
                            <th class="py-2 pl-3">Authorized Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite/10">
                        @foreach ($assignedClients as $cl)
                            <tr>
                                <td class="py-3 pr-3 font-semibold text-graphite">
                                    {{ $cl->name }}
                                    <span class="block text-[11px] font-normal text-graphite/65">{{ $cl->email }}</span>
                                </td>
                                <td class="py-3 px-3">
                                    {{ $cl->customerProfile?->fitness_goal ?? 'Pending Intake' }}
                                    <span class="block text-[11px] text-graphite/65">{{ $cl->customerProfile?->experience_level }}</span>
                                </td>
                                <td class="py-3 px-3">{{ $cl->privateFiles->count() }} private file(s)</td>
                                <td class="py-3 pl-3">
                                    <x-ui.button :href="route('manage.clients.confidential', $cl)" variant="graphite" size="sm">
                                        Open Confidential File
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @php
        $assignedIntakes = $assignedIntakes ?? collect();
        $assignedPlanRequests = $assignedPlanRequests ?? collect();
        $assignedCheckIns = $assignedCheckIns ?? collect();
        $assignedConsultations = $assignedConsultations ?? collect();
        $assignedMessages = $assignedMessages ?? collect();
        $workoutPrograms = $workoutPrograms ?? collect();
    @endphp

    {{-- STAGE 4: SCOPED COACHING & DIET DELIVERY WORKFLOW FOR ASSIGNED CLIENTS --}}
    <div id="coaching-delivery-ops" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="delivery" x-show="tab === 'delivery'" x-cloak>
        {{-- 12-Point Intake Review & Personalised Diet Plan Delivery --}}
        <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 8 · Personalised Diet Plan Preparation & Medical Safeguards</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Assigned Intakes & Custom Diet Delivery ({{ $assignedPlanRequests->count() }})</h2>
                </div>
                <x-ui.badge tone="gold">diet.requests.handle</x-ui.badge>
            </div>

            @if ($assignedIntakes->isNotEmpty())
                <div class="space-y-3 text-xs">
                    @foreach ($assignedIntakes as $intake)
                        <div class="rounded-xs border {{ $intake->needs_professional_review ? 'border-danger/50 bg-danger/5' : 'border-graphite/12 bg-paper' }} p-3.5 space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <strong>Intake #{{ $intake->id }} — {{ $intake->user?->name }} ({{ $intake->fitness_goal }})</strong>
                                <span class="rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ str_replace('_', ' ', $intake->review_status) }}</span>
                            </div>
                            <p class="text-graphite/75">
                                {{ $intake->age }}y · {{ $intake->height_cm }}cm · {{ $intake->weight_kg }}kg · {{ $intake->activity_level }} · {{ $intake->equipment_availability }}
                            </p>
                            <p class="text-graphite/75"><strong>Preferences:</strong> {{ $intake->food_preferences }} | <strong>Allergies:</strong> {{ $intake->allergies ?? 'None' }}</p>
                            @if ($intake->review_flag_reason)
                                <p class="text-danger font-semibold">{{ $intake->review_flag_reason }}</p>
                            @endif

                            <form method="POST" action="{{ route('manage.intakes.review', $intake) }}" class="flex flex-wrap items-center gap-2 pt-1">
                                @csrf
                                <select name="review_status" class="field-input py-1 text-[11px] w-44">
                                    <option value="cleared" @selected($intake->review_status === 'cleared')>Clear Intake for Plan Delivery</option>
                                    <option value="flagged_medical" @selected($intake->review_status === 'flagged_medical')>Flag Medical Hold</option>
                                </select>
                                <input type="text" name="reviewer_notes" value="{{ $intake->reviewer_notes }}" placeholder="Qualification / review note..." class="field-input py-1 text-[11px] flex-1" />
                                <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 text-[10px] font-bold uppercase text-gold cursor-pointer">Update Review</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            @foreach ($assignedPlanRequests as $req)
                <form method="POST" action="{{ route('manage.diet-requests.deliver', $req) }}" class="space-y-3 rounded-xs border border-graphite/15 bg-paper p-4 text-xs">
                    @csrf
                    <div class="flex items-center justify-between">
                        <strong class="text-graphite">Deliver / Revise Diet Plan for {{ $req->user?->name }} ({{ $req->dietPlan?->title }})</strong>
                        <span class="font-mono text-[11px] text-gold-dark">Status: {{ strtoupper($req->status) }}</span>
                    </div>
                    @if ($req->customer_revision_notes)
                        <p class="rounded-xs bg-white p-2 text-graphite border-l-2 border-gold-dark">
                            <strong>Client Revision Request:</strong> {{ $req->customer_revision_notes }}
                        </p>
                    @endif
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        <div class="col-span-2">
                            <label class="field-label text-[11px]">Version Title</label>
                            <input type="text" name="title" value="Personalised Meal Architecture — Revision v{{ ($req->deliveredVersion?->version_number ?? 0) + 1 }}" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-[11px]">Kcal</label>
                            <input type="number" name="daily_calories" value="2050" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-[11px]">Protein (g)</label>
                            <input type="number" name="protein_grams" value="175" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-[11px]">Carbs / Fat (g)</label>
                            <div class="flex gap-1">
                                <input type="number" name="carbs_grams" value="190" class="field-input text-xs py-1.5" required />
                                <input type="number" name="fat_grams" value="62" class="field-input text-xs py-1.5" required />
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <input type="text" name="meal_1_items" value="3 whole eggs + 3 egg whites, 1 whole-wheat chapati (50g atta)" class="field-input text-xs py-1.5" placeholder="Meal 1 (Breakfast)" required />
                        <input type="text" name="meal_2_items" value="200g grilled chicken tikka breast, 160g cooked basmati rice, raita" class="field-input text-xs py-1.5" placeholder="Meal 2 (Lunch)" required />
                        <input type="text" name="meal_3_items" value="1 scoop whey isolate + 1 banana + 10g almonds" class="field-input text-xs py-1.5" placeholder="Meal 3 (Pre-Workout)" required />
                        <input type="text" name="meal_4_items" value="180g lean beef qeema, 1 chapati, 1 cup moong daal, salad" class="field-input text-xs py-1.5" placeholder="Meal 4 (Dinner)" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" name="revision_note" placeholder="Revision note (e.g. Adjusted dinner carbs)..." class="field-input text-xs py-1.5 flex-1" />
                        <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-[11px]">Publish Diet Version & Grant Entitlement</button>
                    </div>
                </form>
            @endforeach
        </section>

        {{-- Check-In Feedback, Custom Workout Assignment, Consultations & Messages --}}
        <section class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-5">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 5, 6 & 9 · Coaching Delivery & Client Feedback</span>
                <h2 class="font-display text-2xl uppercase text-graphite">Check-Ins, Workouts & Messages</h2>
            </div>

            {{-- Assign Workout Form --}}
            @if ($assignedClients->isNotEmpty() && $workoutPrograms->isNotEmpty())
                <form method="POST" action="{{ route('manage.workouts.assign') }}" class="space-y-2 rounded-xs bg-paper p-3.5 text-xs">
                    @csrf
                    <strong class="uppercase text-graphite block">Assign Custom Workout Program</strong>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <select name="client_id" class="field-input text-xs py-1.5" required>
                            @foreach ($assignedClients as $cl)
                                <option value="{{ $cl->id }}">{{ $cl->name }}</option>
                            @endforeach
                        </select>
                        <select name="workout_program_id" class="field-input text-xs py-1.5" required>
                            @foreach ($workoutPrograms as $wp)
                                <option value="{{ $wp->id }}">{{ $wp->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="text" name="custom_guidance" placeholder="Custom RPE / exercise modification notes..." class="field-input text-xs py-1.5" />
                    <button type="submit" class="btn-light-primary w-full rounded-xs px-3 py-2 text-[11px]">Assign Workout & Unlock Entitlement</button>
                </form>
            @endif

            {{-- Client Check-In Feedback Queue --}}
            <div class="space-y-2.5 text-xs">
                <strong class="uppercase text-graphite block">Recent Client Check-Ins ({{ $assignedCheckIns->count() }})</strong>
                @foreach ($assignedCheckIns->take(3) as $ci)
                    <form method="POST" action="{{ route('manage.check-ins.review', $ci) }}" class="rounded-xs border border-graphite/12 bg-paper p-3 space-y-1.5">
                        @csrf
                        <div class="flex items-center justify-between">
                            <strong>{{ $ci->user?->name }} — {{ $ci->weight_kg }} kg / {{ $ci->waist_cm ?? '—' }} cm</strong>
                            <span class="font-mono text-[10px] text-graphite/65">{{ $ci->check_in_date->format('d M Y') }}</span>
                        </div>
                        <p class="text-[11px] text-graphite/75">{{ $ci->client_notes }}</p>
                        <div class="flex gap-1.5">
                            <input type="text" name="coach_feedback" value="{{ $ci->coach_feedback }}" placeholder="Write coach check-in feedback..." class="field-input text-xs py-1 flex-1" required />
                            <button type="submit" class="rounded-xs bg-charcoal px-2.5 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">Save</button>
                        </div>
                    </form>
                @endforeach
            </div>

            {{-- Assigned Client Messaging --}}
            @if ($assignedClients->isNotEmpty())
                <form method="POST" action="{{ route('manage.clients.messages.store', $assignedClients->first()) }}" class="space-y-2 border-t border-graphite/10 pt-3 text-xs">
                    @csrf
                    <strong class="uppercase text-graphite block">Send Direct Message to {{ $assignedClients->first()->name }}</strong>
                    <div class="flex gap-2">
                        <input type="text" name="body" placeholder="Coach update or check-in reminder..." class="field-input text-xs py-1.5 flex-1" required />
                        <button type="submit" class="btn-light-primary rounded-xs px-3 py-1.5 text-[11px]">Send</button>
                    </div>
                </form>
            @endif
        </section>
    </div>

    {{-- MODULE 6 & 7: Stage 5 Physical Gym Reception, QR Check-In Desk, Counter Registration, Classes & Enquiries --}}
    <div id="gym-operations-desk" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="gym" x-show="tab === 'gym'" x-cloak>
        {{-- Left: Staff Check-In Verification Desk & Counter Walk-In Registration --}}
        <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 7 · Reception Check-In &amp; Counter Desk</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Staff Gym Check-In &amp; Counter Registration</h2>
                </div>
                <x-ui.badge tone="gold">gym.checkins.record</x-ui.badge>
            </div>

            {{-- Desk / QR Check-In Form --}}
            <form method="POST" action="{{ route('manage.gym.check-ins.store') }}" class="rounded-xs bg-paper p-4 space-y-3 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Verify Member Check-In (Desk Card Code or Signed QR Token)</strong>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                    <div>
                        <label class="field-label text-xs">Verification Mode</label>
                        <select name="check_in_mode" class="field-input text-xs py-1.5">
                            <option value="desk">Desk Card / Member #</option>
                            <option value="qr">Signed QR Pass Token</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label text-xs">Member Card Code (e.g. BMF-CARD-1001)</label>
                        <input type="text" name="member_identifier" value="BMF-CARD-1001" class="field-input text-xs py-1.5" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <input type="text" name="qr_token" placeholder="Paste signed QR token (if QR mode)..." class="field-input text-xs py-1.5" />
                    <input type="text" name="entry_note" placeholder="Entry note (e.g. Evening strength session)" class="field-input text-xs py-1.5" />
                </div>
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs w-full">Verify &amp; Record Gym Check-In</button>
            </form>

            {{-- Counter Walk-In Registration & Offline Payment Confirmation --}}
            @can('gym.memberships.manage')
                <form method="POST" action="{{ route('manage.gym.counter-register') }}" class="rounded-xs border border-graphite/15 p-4 space-y-3 text-xs">
                    @csrf
                    <strong class="uppercase text-graphite block">Counter Walk-In Registration / Renewal &amp; Offline Payment Verification</strong>
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <div>
                            <label class="field-label text-xs">Member Account</label>
                            <select name="user_id" class="field-input text-xs py-1.5">
                                @foreach ($customersForStatus as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->phone }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label text-xs">Membership Package</label>
                            <select name="membership_plan_id" class="field-input text-xs py-1.5">
                                @foreach ($membershipPlans as $mp)
                                    <option value="{{ $mp->id }}">{{ $mp->name }} ({{ \App\Support\Money::format($mp->price) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label text-xs">Assign Floor Trainer</label>
                            <select name="assigned_trainer_id" class="field-input text-xs py-1.5">
                                <option value="">Unassigned / Floor Team</option>
                                @foreach ($staffTrainers as $tr)
                                    <option value="{{ $tr->id }}">{{ $tr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label text-xs">Counter Receipt Ref</label>
                            <input type="text" name="counter_receipt_ref" placeholder="COUNTER-REC-501" class="field-input text-xs py-1.5" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-graphite">
                        <input type="checkbox" name="verify_payment_immediately" value="1" checked class="accent-gold" />
                        <span>Confirm offline counter payment received &amp; activate membership immediately</span>
                    </label>
                    <button type="submit" class="rounded-xs bg-charcoal px-4 py-2 text-xs font-bold uppercase text-gold w-full cursor-pointer">
                        Process Counter Registration / Renewal
                    </button>
                </form>
            @endcan

            {{-- Member Directory & Expiry Reminder Follow-Up --}}
            <div class="space-y-2 text-xs">
                <strong class="uppercase text-gold-dark block">Active Gym Member Directory ({{ $gymMemberships->count() }})</strong>
                @foreach ($gymMemberships->take(4) as $gm)
                    <form method="POST" action="{{ route('manage.gym.memberships.update', $gm) }}" class="rounded-xs bg-paper p-3 space-y-2">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <strong class="text-graphite">{{ $gm->user?->name }} ({{ $gm->membership_number }})</strong>
                                <span class="block text-[11px] text-graphite/70">
                                    Card: <code class="font-mono">{{ $gm->member_card_code }}</code> · Ends: {{ $gm->ends_at?->format('d M Y') ?? 'Pending' }} · Visits: {{ $gm->total_visits_count }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <select name="status" class="field-input text-[11px] py-1">
                                    @foreach (['active', 'paused', 'expired', 'cancelled'] as $st)
                                        <option value="{{ $st }}" @selected($gm->status === $st)>{{ strtoupper($st) }}</option>
                                    @endforeach
                                </select>
                                <label class="inline-flex items-center gap-1 text-[10px]">
                                    <input type="checkbox" name="send_expiry_reminder" value="1" /> Remind
                                </label>
                                <button type="submit" class="rounded-xs bg-charcoal px-2.5 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">Update</button>
                            </div>
                        </div>
                    </form>
                @endforeach
            </div>
        </section>

        {{-- Right: Class Scheduling & Trial Session / Enquiries Queue --}}
        <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 6 &amp; 10 · Class Schedule &amp; Trial Enquiries</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Schedule Classes ({{ $gymClasses->count() }}) &amp; Trial Desk ({{ $enquiries->count() }})</h2>
                </div>
                <x-ui.badge tone="light">gym.classes.manage</x-ui.badge>
            </div>

            <form method="POST" action="{{ route('manage.gym.classes.store') }}" class="rounded-xs bg-paper p-4 space-y-2.5 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Schedule New Physical Gym Class</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <input type="text" name="title" placeholder="Class Title (e.g. Squat Clinic)" class="field-input text-xs py-1.5 sm:col-span-2" required />
                    <select name="category" class="field-input text-xs py-1.5">
                        @foreach (\App\Models\GymClass::CATEGORIES as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    <select name="day_of_week" class="field-input text-xs py-1.5">
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                        <option value="7">Sunday</option>
                    </select>
                    <input type="time" name="starts_at_time" value="18:30" class="field-input text-xs py-1.5" required />
                    <input type="time" name="ends_at_time" value="19:30" class="field-input text-xs py-1.5" required />
                    <input type="number" name="capacity" value="14" min="1" max="60" placeholder="Capacity" class="field-input text-xs py-1.5" required />
                    <input type="text" name="room_or_zone" value="Main Strength Floor" class="field-input text-xs py-1.5 sm:col-span-2" />
                </div>
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs w-full">Schedule Gym Class</button>
            </form>

            {{-- Enquiries & Trial Bookings Queue --}}
            <div class="space-y-2 text-xs">
                <strong class="uppercase text-gold-dark block">Trial Session Bookings &amp; Facility Enquiries</strong>
                @foreach ($enquiries->take(4) as $enq)
                    <form method="POST" action="{{ route('manage.gym.enquiries.update', $enq) }}" class="rounded-xs border border-graphite/12 p-3 space-y-2">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <strong class="text-graphite">{{ $enq->name }} ({{ $enq->phone }})</strong>
                                <span class="block text-[11px] text-graphite/70">
                                    {{ strtoupper($enq->type) }}
                                    @if ($enq->preferred_trial_date) · Trial: {{ $enq->preferred_trial_date->format('d M Y') }} ({{ $enq->preferred_time_window }}) @endif
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <select name="status" class="field-input text-[11px] py-1">
                                    @foreach (['new', 'contacted', 'trial_scheduled', 'converted', 'closed'] as $est)
                                        <option value="{{ $est }}" @selected($enq->status === $est)>{{ strtoupper($est) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-xs bg-charcoal px-2.5 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">Save</button>
                            </div>
                        </div>
                        <input type="text" name="staff_notes" value="{{ $enq->staff_notes }}" placeholder="Follow-up notes..." class="field-input text-xs py-1" />
                    </form>
                @endforeach
            </div>
        </section>
    </div>

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

    {{-- STAGE 6 MODULE 10, 11 & 12: Manager Support Tickets, Limited Operational Reports & Approved Promotions --}}
    <div id="support-tickets" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="support" x-show="tab === 'support'" x-cloak>
        <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 10 · Customer Support Queue</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Assigned &amp; Operational Support Tickets</h2>
                </div>
                <x-ui.badge tone="gold">{{ $supportTickets->count() }} Tickets</x-ui.badge>
            </div>

            <div class="space-y-3 text-xs">
                @foreach ($supportTickets as $ticket)
                    <div class="rounded-xs border border-graphite/15 bg-paper p-3.5 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono font-bold text-gold-dark">{{ $ticket->ticket_number }}</span>
                                <span class="ml-1 rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $ticket->category }}</span>
                                <strong class="block mt-1 text-graphite">{{ $ticket->subject }}</strong>
                                <span class="text-[11px] text-graphite/70">Customer: {{ $ticket->user?->name }}</span>
                            </div>
                            <x-ui.badge :tone="$ticket->status === 'resolved' ? 'success' : 'gold'">{{ $ticket->status }}</x-ui.badge>
                        </div>

                        <div class="max-h-32 overflow-y-auto space-y-1 rounded-xs bg-white p-2 border border-graphite/10">
                            @foreach ($ticket->messages as $msg)
                                <div class="rounded-xs p-1.5 {{ $msg->is_internal_note ? 'bg-amber-50 border border-amber-300' : 'bg-paper' }}">
                                    <strong class="text-[10px] text-graphite/70">{{ $msg->sender?->name }} @if ($msg->is_internal_note) [INTERNAL NOTE] @endif:</strong>
                                    <p class="text-graphite">{{ $msg->message }}</p>
                                </div>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('manage.support-tickets.reply', $ticket) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="text" name="message" placeholder="Write response or internal note..." class="field-input py-1 text-xs flex-1" required />
                            <label class="inline-flex items-center gap-1 text-[11px]">
                                <input type="checkbox" name="is_internal_note" value="1" /> Internal
                            </label>
                            <button type="submit" class="rounded-xs bg-charcoal px-3 py-1 font-bold uppercase text-gold cursor-pointer">Reply</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

    </div>

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

    <div data-tab-panel="security" x-show="tab === 'security'" x-cloak>
        <x-auth.two-factor-panel />
    </div>
</x-layouts.dashboard>
