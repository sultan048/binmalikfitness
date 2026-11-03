@php
    use App\Support\Money;
    $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    $seoService = app(\App\Domain\Platform\SeoAndStructuredDataService::class);
    $gymSchemas = [
        $seoService->organizationAndHealthClubSchema(),
        $seoService->breadcrumbSchema([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Physical Strength Gym', 'url' => route('gym.index')],
        ]),
    ];
@endphp

<x-layouts.public
    title="Physical Strength Gym, Memberships, Class Schedule & Trial Booking — BIN MALIK FITNESS"
    description="Join the BIN MALIK FITNESS physical strength facility in Pakistan. View PKR membership packages, barbell class schedules, operating hours, and book a trial session."
    :canonical="route('gym.index')"
    :json-ld-schemas="$gymSchemas"
>
    {{-- 1. Hero Section --}}
    <section class="section-y bg-ink border-b border-white/10">
        <div class="container-site space-y-8">
            @if (session('status'))
                <div class="rounded-sm border border-gold/40 bg-gold/10 px-4 py-3 text-sm font-medium text-ivory">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-sm border border-danger bg-danger/15 px-4 py-3 text-xs text-ivory">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-end">
                <div class="lg:col-span-8 space-y-4">
                    <span class="eyebrow text-gold">Business Line 04 · Physical Strength Facility</span>
                    <h1 class="display-xl text-ivory">
                        PHYSICAL GYM MEMBERSHIPS,<br />
                        BARBELL CLASSES &amp; TRIAL DESK
                    </h1>
                    <p class="max-w-2xl text-base text-silver leading-relaxed">
                        Calibrated barbell platforms, heavy dumbbell bays, structured group technique clinics, and staff-verified member check-ins. Purchase or renew your membership online or at the gym reception counter.
                    </p>
                </div>
                <div class="lg:col-span-4 flex lg:justify-end">
                    <div class="rounded-xs border border-gold/40 bg-charcoal px-4 py-3 text-xs text-gold space-y-1">
                        <div class="flex items-center gap-2 font-bold uppercase tracking-wider">
                            <span class="h-2 w-2 rounded-full bg-gold"></span>
                            <span>Stage 5 Live Gym Operations</span>
                        </div>
                        <p class="text-[11px] text-silver">
                            Location: {{ $gymSettings['gym.city'] ?? 'Configurable City (Pending Owner Confirmation)' }}
                        </p>
                    </div>
                </div>
            </div>

            @if ($activeMembership)
                <div class="rounded-sm border border-gold/50 bg-charcoal p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-xs bg-gold px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-ink">
                                Active Gym Member · {{ $activeMembership->membership_number }}
                            </span>
                            <span class="font-mono text-xs text-gold">Card Code: {{ $activeMembership->member_card_code }}</span>
                        </div>
                        <h2 class="font-display text-2xl text-ivory">{{ $activeMembership->plan->name }}</h2>
                        <p class="text-xs text-silver">
                            Valid until <strong class="text-ivory">{{ $activeMembership->ends_at?->format('d M Y') }}</strong>
                            ({{ $activeMembership->daysRemaining() }} days remaining) ·
                            Total Checked-In Visits: <strong class="text-ivory">{{ $activeMembership->total_visits_count }}</strong>
                            @if ($activeMembership->assignedTrainer)
                                · Assigned Floor Trainer: <strong class="text-gold">{{ $activeMembership->assignedTrainer->name }}</strong>
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('account.dashboard') }}#gym-membership-card" class="rounded-xs bg-gold px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-ink hover:bg-gold/90">
                            Open Digital Member Card &amp; QR Pass →
                        </a>
                        <a href="#class-schedule" class="rounded-xs border border-white/20 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-ivory hover:border-gold hover:text-gold">
                            Book Group Class
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- 2. Membership Packages & Online / Counter Purchase or Renewal --}}
    <section id="membership-packages" class="section-y bg-ink border-b border-white/10">
        <div class="container-site space-y-10">
            <x-ui.section-heading
                eyebrow="Physical Gym Tiers · PKR"
                title="Membership Packages & Renewal"
                subtitle="Select a membership package below. Online bank transfer and counter cash orders are activated as soon as our reception or operations team verifies payment."
            />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($plans as $plan)
                    <article class="flex flex-col justify-between rounded-sm border {{ $plan->is_featured ? 'border-gold bg-charcoal shadow-xl' : 'border-white/12 bg-charcoal/80' }} p-6 space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="eyebrow text-gold">{{ $plan->duration_days }} Days Access</span>
                                @if ($plan->is_featured)
                                    <span class="rounded-xs bg-gold px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-ink">Most Popular</span>
                                @endif
                            </div>

                            <h3 class="font-display text-3xl text-ivory">{{ $plan->name }}</h3>

                            <div class="border-y border-white/10 py-4 space-y-1">
                                <div class="flex items-baseline gap-2">
                                    <span class="font-display text-4xl text-gold">{{ Money::format($plan->price) }}</span>
                                    <span class="text-xs text-silver">/ {{ $plan->duration_days }} days</span>
                                </div>
                                <p class="text-xs text-silver">
                                    @if ($plan->registration_fee > 0)
                                        One-time registration fee: <strong class="text-ivory">{{ Money::format($plan->registration_fee) }}</strong> (Waived on renewals)
                                    @else
                                        <strong class="text-gold">PKR 0 Registration Fee</strong> (Waived)
                                    @endif
                                </p>
                                <p class="text-[11px] text-silver/80">Access Window: {{ $plan->access_hours }}</p>
                            </div>

                            <ul class="space-y-2 text-xs text-ivory/90">
                                @foreach ($plan->inclusions ?? [] as $inc)
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1 h-1.5 w-1.5 bg-gold shrink-0"></span>
                                        <span>{{ $inc }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="pt-4 border-t border-white/10">
                            @auth
                                <form method="POST" action="{{ route('gym.memberships.purchase', $plan->slug) }}" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="is_renewal" value="{{ $activeMembership ? '1' : '0' }}" />
                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-silver mb-1">
                                            Payment Method
                                        </label>
                                        <select name="payment_method" class="field-input-dark text-xs">
                                            <option value="bank_transfer">Manual Bank Transfer (Upload Receipt)</option>
                                            <option value="counter_cash">Pay at Gym Reception Counter (Offline Verification)</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="w-full rounded-xs {{ $plan->is_featured ? 'bg-gold text-ink' : 'border border-gold text-gold hover:bg-gold hover:text-ink' }} px-4 py-3 text-xs font-bold uppercase tracking-wider transition cursor-pointer">
                                        {{ $activeMembership ? 'Renew with This Package' : 'Join Gym with This Package' }}
                                    </button>
                                </form>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="block w-full text-center rounded-xs {{ $plan->is_featured ? 'bg-gold text-ink' : 'border border-white/25 text-ivory hover:border-gold hover:text-gold' }} px-4 py-3 text-xs font-bold uppercase tracking-wider transition"
                                >
                                    Sign In to Join or Renew
                                </a>
                            @endauth
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 3. Class Schedule & Capacity-Checked Bookings --}}
    <section id="class-schedule" class="section-y bg-ink border-b border-white/10">
        <div class="container-site space-y-8">
            <x-ui.section-heading
                eyebrow="Coach-Led Floor Sessions · Capacity Controlled"
                title="Weekly Barbell & Conditioning Class Schedule"
                subtitle="Classes are included for active gym members and strictly capped to preserve platform and coaching ratios."
            />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                @foreach ($classes as $gymClass)
                    @php
                        $isBooked = in_array($gymClass->id, $bookedClassIds, true);
                        $spotsLeft = $gymClass->remainingSpots();
                    @endphp
                    <article class="rounded-sm border border-white/12 bg-charcoal p-6 flex flex-col justify-between gap-4">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="eyebrow text-gold">{{ $gymClass->category }} · {{ $dayNames[$gymClass->day_of_week] ?? 'Scheduled' }}</span>
                                <span class="rounded-xs border {{ $spotsLeft > 0 ? 'border-gold/40 text-gold' : 'border-danger text-danger-on-dark' }} bg-ink px-2.5 py-0.5 font-mono text-[11px]">
                                    {{ $gymClass->booked_count }} / {{ $gymClass->capacity }} Booked ({{ $spotsLeft }} left)
                                </span>
                            </div>

                            <h3 class="font-display text-2xl text-ivory">{{ $gymClass->title }}</h3>
                            <p class="text-xs text-silver leading-relaxed">{{ $gymClass->summary }}</p>

                            <div class="grid grid-cols-2 gap-2 pt-2 text-xs text-silver border-t border-white/10">
                                <div>
                                    <span class="block text-[10px] uppercase text-silver/70">Timing &amp; Date</span>
                                    <strong class="text-ivory">
                                        {{ substr($gymClass->starts_at_time, 0, 5) }} – {{ substr($gymClass->ends_at_time, 0, 5) }}
                                        @if ($gymClass->session_date)
                                            ({{ $gymClass->session_date->format('d M') }})
                                        @endif
                                    </strong>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase text-silver/70">Zone &amp; Coach</span>
                                    <strong class="text-ivory">
                                        {{ $gymClass->room_or_zone }} · {{ $gymClass->instructor?->name ?? 'Floor Coach' }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-white/10 flex items-center justify-between gap-3">
                            @auth
                                @if ($isBooked)
                                    @php
                                        $userBooking = \App\Models\GymClassBooking::where('gym_class_id', $gymClass->id)
                                            ->where('user_id', auth()->id())
                                            ->where('status', 'booked')
                                            ->first();
                                    @endphp
                                    <span class="text-xs font-semibold text-gold">✓ Spot Reserved</span>
                                    @if ($userBooking)
                                        <form method="POST" action="{{ route('gym.class-bookings.cancel', $userBooking) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-xs border border-white/20 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-silver hover:border-danger hover:text-ivory cursor-pointer">
                                                Cancel Spot
                                            </button>
                                        </form>
                                    @endif
                                @elseif ($spotsLeft === 0)
                                    <span class="text-xs font-bold uppercase text-danger-on-dark">Session Full (Capacity Reached)</span>
                                @else
                                    <span class="text-[11px] text-silver">Requires active gym membership</span>
                                    <form method="POST" action="{{ route('gym.classes.book', $gymClass) }}">
                                        @csrf
                                        <button type="submit" class="rounded-xs bg-gold px-4 py-2 text-xs font-bold uppercase tracking-wider text-ink hover:bg-gold/90 cursor-pointer">
                                            Book Class Spot
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="text-[11px] text-silver">Members-only class booking</span>
                                <a href="{{ route('login') }}" class="rounded-xs border border-gold px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-gold hover:bg-gold hover:text-ink">
                                    Sign In to Book
                                </a>
                            @endauth
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Gym Operating Hours, Holiday Schedules & Trial Session Booking --}}
    <section id="timings-and-trial" class="section-y bg-ink">
        <div class="container-site grid grid-cols-1 gap-10 lg:grid-cols-12">
            {{-- Left: Operating Hours & Holidays --}}
            <div class="lg:col-span-6 space-y-6">
                <div class="space-y-2">
                    <span class="eyebrow text-gold">Weekly Timings &amp; Holiday Notices</span>
                    <h2 class="display-m text-ivory">Gym Operating Hours</h2>
                    <p class="text-xs text-silver">
                        Facility Location: <strong class="text-ivory">{{ $gymSettings['gym.address'] ?? 'Address Pending Owner Confirmation' }}</strong>
                        ({{ $gymSettings['gym.city'] ?? 'Pakistan' }})
                    </p>
                </div>

                <div class="rounded-sm border border-white/12 bg-charcoal overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-ink text-[11px] uppercase tracking-wider text-gold border-b border-white/10">
                            <tr>
                                <th class="py-3 px-4">Day</th>
                                <th class="py-3 px-4">Operating Hours</th>
                                <th class="py-3 px-4">Dedicated / Peak Windows</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 text-ivory">
                            @foreach ($openingHours as $hour)
                                <tr>
                                    <td class="py-3 px-4 font-semibold">{{ $hour->day_name }}</td>
                                    <td class="py-3 px-4 font-mono">
                                        @if ($hour->is_closed)
                                            <span class="text-danger-on-dark font-bold uppercase">Closed</span>
                                        @else
                                            {{ substr((string) $hour->opens_at, 0, 5) }} – {{ substr((string) $hour->closes_at, 0, 5) }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-silver">
                                        @if ($hour->ladies_only_window)
                                            <span class="block">Ladies: {{ $hour->ladies_only_window }}</span>
                                        @endif
                                        @if ($hour->peak_window)
                                            <span class="block text-[11px] text-silver/80">Peak: {{ $hour->peak_window }}</span>
                                        @endif
                                        @if ($hour->note)
                                            <span class="block text-[11px] text-gold">{{ $hour->note }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($holidays->isNotEmpty())
                    <div class="rounded-sm border border-gold/30 bg-charcoal p-5 space-y-3">
                        <h3 class="font-display text-xl text-gold">Upcoming Holiday &amp; Maintenance Schedule</h3>
                        <div class="space-y-2.5 text-xs">
                            @foreach ($holidays as $holiday)
                                <div class="rounded-xs bg-ink p-3 flex flex-wrap items-center justify-between gap-2 border border-white/10">
                                    <div>
                                        <strong class="text-ivory">{{ $holiday->title }}</strong>
                                        <span class="block text-silver">{{ $holiday->note }}</span>
                                    </div>
                                    <div class="text-right font-mono">
                                        <span class="block text-gold">{{ $holiday->holiday_date->format('d M Y') }}</span>
                                        <span class="text-[11px] {{ $holiday->is_closed ? 'text-danger-on-dark' : 'text-ivory' }}">
                                            {{ $holiday->is_closed ? 'FACILITY CLOSED' : substr((string) $holiday->special_opens_at, 0, 5).' – '.substr((string) $holiday->special_closes_at, 0, 5) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right: Trial Session Booking & Facility Enquiry Form --}}
            <div class="lg:col-span-6">
                <div class="rounded-sm border border-gold/40 bg-charcoal p-6 sm:p-8 space-y-6">
                    <div class="space-y-2">
                        <span class="eyebrow text-gold">Walk-In &amp; Trial Desk</span>
                        <h2 class="display-m text-ivory">Book a Gym Trial Session or Enquire</h2>
                        <p class="text-xs text-silver leading-relaxed">
                            No account required. Reserve a supervised trial session or ask our reception desk about membership packages and personal trainer availability.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('gym.enquiries.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="field-label text-xs text-ivory">Request Type</label>
                                <select name="type" class="field-input-dark text-xs">
                                    <option value="gym_trial">1-Day Gym Trial Session Booking</option>
                                    <option value="membership">Membership &amp; Corporate Pricing Enquiry</option>
                                    <option value="general">General Facility Question</option>
                                </select>
                            </div>
                            <div>
                                <label class="field-label text-xs text-ivory">Interested Package</label>
                                <select name="interested_membership_plan_id" class="field-input-dark text-xs">
                                    <option value="">Undecided / Compare at Desk</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }} ({{ Money::format($plan->price) }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="field-label text-xs text-ivory">Full Name *</label>
                                <input
                                    type="text"
                                    name="name"
                                    required
                                    value="{{ old('name', auth()->user()?->name) }}"
                                    placeholder="e.g. Hamza Tariq"
                                    class="field-input-dark text-xs"
                                />
                            </div>
                            <div>
                                <label class="field-label text-xs text-ivory">Pakistan Mobile Number *</label>
                                <input
                                    type="text"
                                    name="phone"
                                    required
                                    value="{{ old('phone', auth()->user()?->phone) }}"
                                    placeholder="0300-1234567"
                                    class="field-input-dark text-xs"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="field-label text-xs text-ivory">Email (Optional)</label>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email', auth()->user()?->email) }}"
                                    placeholder="you@example.com"
                                    class="field-input-dark text-xs"
                                />
                            </div>
                            <div>
                                <label class="field-label text-xs text-ivory">Preferred Trial Date</label>
                                <input
                                    type="date"
                                    name="preferred_trial_date"
                                    value="{{ old('preferred_trial_date', now()->addDay()->toDateString()) }}"
                                    class="field-input-dark text-xs"
                                />
                            </div>
                            <div>
                                <label class="field-label text-xs text-ivory">Preferred Time Window</label>
                                <select name="preferred_time_window" class="field-input-dark text-xs">
                                    <option value="Morning (07:00 – 11:00)">Morning (07:00 – 11:00)</option>
                                    <option value="Afternoon (14:00 – 17:30)">Afternoon (14:00 – 17:30)</option>
                                    <option value="Evening (18:00 – 21:30)">Evening (18:00 – 21:30)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="field-label text-xs text-ivory">Training Goals or Questions for Reception</label>
                            <textarea
                                name="message"
                                rows="3"
                                placeholder="Tell us if you want to test the power racks, meet a strength trainer, or ask about timings..."
                                class="field-input-dark text-xs"
                            >{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="w-full rounded-xs bg-gold px-6 py-3.5 text-xs font-bold uppercase tracking-wider text-ink hover:bg-gold/90 cursor-pointer">
                            Submit Trial Booking / Gym Enquiry
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
