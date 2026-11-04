{{-- Tab panel "gym" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
@php
    use App\Support\Money;
@endphp
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
            <strong class="uppercase text-gold-dark block">Active Gym Member Directory ({{ $gymMembershipsCount }})</strong>
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
