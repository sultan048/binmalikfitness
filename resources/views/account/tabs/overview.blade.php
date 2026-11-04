{{-- Tab panel "overview" of account/dashboard (included from the parent view, shares its variables) --}}
<section id="member-overview" class="space-y-4" data-tab-panel="overview" x-show="tab === 'overview'" x-cloak>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="Current Fitness Goal"
            :value="$profile?->fitness_goal ?? 'Set Your Goal'"
            :meta="($profile?->height_cm ? $profile->height_cm.' cm · ' : '').($profile?->weight_kg ? $profile->weight_kg.' kg' : 'Update metrics below')"
        />
        <x-ui.stat-card
            label="Assigned Coaching Staff"
            :value="$user->coachAssignmentsAsClient->count().' Assigned'"
            meta="Dedicated coach & nutritionist scope"
        />
        <x-ui.stat-card
            label="Active Entitlements & Orders"
            :value="$entitlements->count().' Entitlements · '.$orders->count().' Orders'"
            meta="Instant PDF, Video & Coaching access"
        />
        <x-ui.stat-card
            label="Support & Alerts"
            :value="$supportTickets->count().' Tickets · '.$roleNotifications->whereNull('read_at')->count().' Unread'"
            meta="Direct staff & coach communication"
        />
    </div>

    {{-- Today's Scheduled Activities & Role Notifications --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
        <div class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-4 space-y-2 text-xs">
            <div class="flex items-center justify-between">
                <strong class="uppercase text-gold-dark">Today’s Training, Class &amp; Consultation Schedule</strong>
                <span class="font-mono text-[11px] text-graphite/65">{{ now()->format('l, d M Y') }}</span>
            </div>
            <ul class="space-y-1.5 text-graphite">
                @foreach ($workoutAssignments->take(2) as $wa)
                    <li class="rounded-xs bg-paper px-3 py-1.5 flex items-center justify-between">
                        <span><strong>Workout:</strong> {{ $wa->program?->title }} ({{ $wa->completed_sessions }}/{{ $wa->target_sessions }} sessions)</span>
                        <span class="uppercase text-[10px] font-bold text-gold-dark">{{ $wa->status }}</span>
                    </li>
                @endforeach
                @foreach ($gymClassBookings->take(2) as $gb)
                    <li class="rounded-xs bg-paper px-3 py-1.5 flex items-center justify-between">
                        <span><strong>Gym Class:</strong> {{ $gb->gymClass?->title }} ({{ substr((string) $gb->gymClass?->starts_at_time, 0, 5) }})</span>
                        <span class="uppercase text-[10px] font-bold text-success">{{ $gb->status }}</span>
                    </li>
                @endforeach
                @foreach ($consultations->take(2) as $con)
                    <li class="rounded-xs bg-paper px-3 py-1.5 flex items-center justify-between">
                        <span><strong>Consultation:</strong> {{ $con->topic }} ({{ $con->scheduled_at?->format('d M H:i') }})</span>
                        <span class="uppercase text-[10px] font-bold text-gold-dark">{{ $con->status }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-4 space-y-2 text-xs">
            <div class="flex items-center justify-between">
                <strong class="uppercase text-gold-dark">Account Notifications ({{ $roleNotifications->whereNull('read_at')->count() }} Unread)</strong>
                <x-ui.badge tone="gold">Live Feed</x-ui.badge>
            </div>
            <div class="space-y-1.5 max-h-36 overflow-y-auto">
                @forelse ($roleNotifications as $notif)
                    <div class="rounded-xs border border-graphite/10 bg-paper px-3 py-2 flex items-center justify-between gap-2">
                        <div>
                            <strong class="text-graphite">{{ $notif->title }}</strong>
                            <p class="text-[11px] text-graphite/75">{{ $notif->body }}</p>
                        </div>
                        @if (! $notif->read_at)
                            <form method="POST" action="{{ route('account.notifications.read', $notif) }}">
                                @csrf
                                <button type="submit" class="rounded-xs bg-charcoal px-2 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">Mark Read</button>
                            </form>
                        @else
                            <span class="text-[10px] uppercase text-graphite/50">Read</span>
                        @endif
                    </div>
                @empty
                    <p class="text-graphite/60">No notifications yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
