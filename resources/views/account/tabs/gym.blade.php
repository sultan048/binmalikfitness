{{-- Tab panel "gym" of account/dashboard (included from the parent view, shares its variables) --}}
<div id="gym-membership-card" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="gym" x-show="tab === 'gym'" x-cloak>
    <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 11 &amp; 12 · Physical Gym Membership Card &amp; QR Pass</span>
                <h2 class="font-display text-2xl uppercase text-graphite">My Physical Gym Membership</h2>
            </div>
            <a href="{{ route('gym.index') }}" class="text-xs font-bold uppercase text-gold-dark hover:underline">Gym Schedule &amp; Tiers →</a>
        </div>

        @if ($gymMembership)
            <div class="rounded-sm border border-gold/50 bg-charcoal p-5 text-ivory space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-gold">BIN MALIK FITNESS · MEMBER ACCESS CARD</span>
                        <h3 class="font-display text-2xl text-ivory">{{ $gymMembership->plan?->name ?? 'Gym Membership' }}</h3>
                    </div>
                    <span class="rounded-xs bg-gold px-2.5 py-1 text-[11px] font-bold uppercase text-ink">
                        {{ $gymMembership->membership_number }} · {{ strtoupper($gymMembership->status) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-4 border-y border-white/10 py-3">
                    <div>
                        <span class="text-[10px] uppercase text-silver block">Card Code</span>
                        <strong class="font-mono text-gold">{{ $gymMembership->member_card_code }}</strong>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-silver block">Valid Until</span>
                        <strong class="text-ivory">{{ $gymMembership->ends_at?->format('d M Y') ?? 'Pending' }} ({{ $gymMembership->daysRemaining() }}d)</strong>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-silver block">Assigned Trainer</span>
                        <strong class="text-ivory">{{ $gymMembership->assignedTrainer?->name ?? 'Floor Team' }}</strong>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-silver block">Total Visits</span>
                        <strong class="text-ivory">{{ $gymMembership->total_visits_count }} Check-Ins</strong>
                    </div>
                </div>

                @if ($qrCheckInToken)
                    <div class="rounded-xs border border-white/15 bg-ink p-3 space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold uppercase text-gold">Signed One-Time QR Check-In Pass (Replay Protected)</span>
                            <span class="text-silver">Staff-Verified Entry Only</span>
                        </div>
                        <code class="block break-all font-mono text-[10px] text-silver select-all">{{ $qrCheckInToken }}</code>
                    </div>
                @endif
            </div>

            {{-- 1-Click Membership Renewal Form --}}
            <form method="POST" action="{{ route('gym.memberships.purchase', $gymMembership->plan?->slug ?? 'quarterly-gym-access-demo') }}" class="flex flex-wrap items-end gap-3 pt-1 text-xs">
                @csrf
                <input type="hidden" name="is_renewal" value="1" />
                <div class="flex-1 min-w-[180px]">
                    <label class="field-label text-xs">Renew Current Gym Package ({{ $gymMembership->plan?->name }})</label>
                    <select name="payment_method" class="field-input text-xs py-1.5">
                        <option value="bank_transfer">Manual Bank Transfer</option>
                        <option value="counter_cash">Pay at Gym Reception Counter</option>
                    </select>
                </div>
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs">Create Renewal Order</button>
            </form>
        @else
            <div class="rounded-xs bg-paper p-4 text-xs text-graphite/80 space-y-2">
                <p>You do not have an active physical gym membership yet.</p>
                <a href="{{ route('gym.index') }}" class="inline-block rounded-xs bg-charcoal px-4 py-2 font-bold uppercase text-gold">Browse Gym Packages →</a>
            </div>
        @endif
    </section>

    {{-- Attendance History & Booked Gym Classes --}}
    <section class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-5">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Attendance &amp; Group Sessions</span>
            <h2 class="font-display text-2xl uppercase text-graphite">Gym Visits ({{ $gymCheckIns->count() }}) &amp; Class Bookings ({{ $gymClassBookings->count() }})</h2>
        </div>

        <div class="space-y-2 text-xs">
            <h3 class="font-bold uppercase text-graphite">Upcoming Booked Classes</h3>
            @forelse ($gymClassBookings as $booking)
                <div class="flex items-center justify-between gap-2 rounded-xs bg-paper p-2.5">
                    <div>
                        <strong class="text-graphite block">{{ $booking->gymClass?->title }}</strong>
                        <span class="text-[11px] text-graphite/70">
                            {{ substr((string) $booking->gymClass?->starts_at_time, 0, 5) }} · {{ $booking->gymClass?->room_or_zone }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('gym.class-bookings.cancel', $booking) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-[11px] font-bold uppercase text-danger hover:underline cursor-pointer">Cancel</button>
                    </form>
                </div>
            @empty
                <p class="text-graphite/65">No upcoming class bookings. <a href="{{ route('gym.index') }}#class-schedule" class="text-gold-dark font-semibold underline">Book a class</a>.</p>
            @endforelse
        </div>

        <div class="space-y-2 border-t border-graphite/10 pt-3 text-xs">
            <h3 class="font-bold uppercase text-graphite">Recent Gym Check-In Attendance</h3>
            @forelse ($gymCheckIns->take(4) as $gci)
                <div class="flex items-center justify-between gap-2 rounded-xs border border-graphite/10 px-3 py-2">
                    <div>
                        <strong class="text-graphite">{{ $gci->checked_in_at?->format('d M Y, H:i') }}</strong>
                        <span class="block text-[11px] text-graphite/70">{{ $gci->entry_note }}</span>
                    </div>
                    <span class="rounded-xs bg-charcoal px-2 py-0.5 font-mono text-[10px] uppercase text-gold">{{ $gci->check_in_method }}</span>
                </div>
            @empty
                <p class="text-graphite/65">No check-in visits recorded yet.</p>
            @endforelse
        </div>
    </section>
</div>
