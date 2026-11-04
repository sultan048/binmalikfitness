{{-- Tab panel "coaching" of account/dashboard (included from the parent view, shares its variables) --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="coaching" x-show="tab === 'coaching'" x-cloak>
    <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 5 & 13 · Coaching Subscription & Manual Renewal</span>
                <h2 class="font-display text-2xl uppercase text-graphite">My 1-to-1 Coaching Subscription</h2>
            </div>
            @if ($activeSubscription)
                <span class="rounded-xs bg-charcoal px-2.5 py-1 text-xs font-bold uppercase text-gold">
                    {{ $activeSubscription->subscription_number }} · {{ strtoupper($activeSubscription->status) }}
                </span>
            @endif
        </div>

        @if ($activeSubscription)
            <div class="grid grid-cols-2 gap-3 rounded-xs bg-paper p-4 text-xs sm:grid-cols-4">
                <div>
                    <span class="text-graphite/60 block">Coaching Tier</span>
                    <strong class="text-graphite">{{ $activeSubscription->package?->name ?? 'Coaching Package' }}</strong>
                </div>
                <div>
                    <span class="text-graphite/60 block">Assigned Coach</span>
                    <strong class="text-graphite">{{ $activeSubscription->coach?->name ?? 'Pending Coach Assignment' }}</strong>
                </div>
                <div>
                    <span class="text-graphite/60 block">Term End Date</span>
                    <strong class="text-graphite">{{ $activeSubscription->ends_at?->format('d M Y') ?? 'Pending Payment Verification' }}</strong>
                </div>
                <div>
                    <span class="text-graphite/60 block">Remaining / Pause Used</span>
                    <strong class="text-gold-dark">{{ $activeSubscription->daysRemaining() }}d left · {{ $activeSubscription->pause_days_used }}/{{ $activeSubscription->max_pause_days }}d paused</strong>
                </div>
            </div>

            @if ($activeSubscription->coach_summary_notes)
                <p class="rounded-xs border-l-2 border-gold-dark bg-paper/70 px-3 py-2 text-xs text-graphite">
                    <strong>Coach Progress Summary:</strong> {{ $activeSubscription->coach_summary_notes }}
                </p>
            @endif

            {{-- Pause / Resume / Renew / Cancel Controls --}}
            <div class="grid grid-cols-1 gap-3 border-t border-graphite/10 pt-4 sm:grid-cols-3 text-xs">
                @if ($activeSubscription->status === 'active')
                    <form method="POST" action="{{ route('account.coaching.subscriptions.pause', $activeSubscription) }}" class="space-y-2 rounded-xs bg-paper p-3">
                        @csrf
                        <span class="font-bold uppercase text-graphite block">Pause Subscription</span>
                        <input type="number" name="pause_days" value="7" min="1" max="14" class="field-input text-xs py-1.5" placeholder="Days (1-14)" required />
                        <input type="text" name="reason" value="Travel / work schedule pause" class="field-input text-xs py-1.5" placeholder="Reason" required />
                        <button type="submit" class="btn-light-primary w-full rounded-xs px-3 py-2 text-[11px]">Pause & Extend Term</button>
                    </form>
                @elseif ($activeSubscription->status === 'paused')
                    <form method="POST" action="{{ route('account.coaching.subscriptions.resume', $activeSubscription) }}" class="space-y-2 rounded-xs bg-paper p-3">
                        @csrf
                        <span class="font-bold uppercase text-graphite block">Subscription Paused</span>
                        <p class="text-[11px] text-graphite/70">Reason: {{ $activeSubscription->pause_reason }}</p>
                        <button type="submit" class="btn-light-primary w-full rounded-xs px-3 py-2 text-[11px]">Resume Coaching Now</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('account.coaching.subscriptions.renew', $activeSubscription) }}" class="space-y-2 rounded-xs bg-paper p-3">
                    @csrf
                    <span class="font-bold uppercase text-gold-dark block">Manual Renewal (No Auto-Debit)</span>
                    <p class="text-[11px] text-graphite/70">Creates a manual renewal order extending your current end date once payment is verified.</p>
                    <button type="submit" class="btn-light-primary w-full rounded-xs px-3 py-2 text-[11px]">Create Renewal Order</button>
                </form>

                @if ($activeSubscription->status !== 'cancelled')
                    <form method="POST" action="{{ route('account.coaching.subscriptions.cancel', $activeSubscription) }}" class="space-y-2 rounded-xs bg-paper p-3">
                        @csrf
                        <span class="font-bold uppercase text-danger block">Cancel Subscription</span>
                        <input type="text" name="reason" placeholder="Cancellation reason..." class="field-input text-xs py-1.5" required />
                        <button type="submit" class="w-full rounded-xs border border-danger px-3 py-2 text-[11px] font-bold uppercase text-danger hover:bg-danger hover:text-white">
                            Cancel & Revoke
                        </button>
                    </form>
                @endif
            </div>
        @else
            <div class="rounded-xs bg-paper p-4 text-xs text-graphite/80 space-y-2">
                <p>You do not have an active 1-to-1 coaching subscription yet.</p>
                <a href="{{ route('coaching.index') }}" class="inline-block font-bold uppercase text-gold-dark underline">Explore 8, 12 & 16-Week Coaching Packages →</a>
            </div>
        @endif
    </section>

    {{-- Active Digital Entitlements & Personalized Diet Plans --}}
    <section class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 6 & 8 · Server-Side Entitlements</span>
                <h2 class="font-display text-2xl uppercase text-graphite">My Diet Plans & Entitlements ({{ $entitlements->count() }})</h2>
            </div>
            <a href="{{ route('coaching.index') }}#diet-plans" class="text-xs font-bold uppercase text-gold-dark hover:underline">All Plans →</a>
        </div>

        @if ($planRequests->isNotEmpty())
            <div class="space-y-2.5 text-xs">
                @foreach ($planRequests as $req)
                    <div class="rounded-xs border border-graphite/12 bg-paper p-3.5 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <strong class="text-graphite">{{ $req->dietPlan?->title ?? 'Custom Diet Plan' }}</strong>
                            <span class="rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ str_replace('_', ' ', $req->status) }}</span>
                        </div>
                        @if ($req->deliveredVersion)
                            <p class="text-graphite/75">
                                Delivered: <strong>{{ $req->deliveredVersion->title }}</strong> ({{ $req->deliveredVersion->daily_calories }} kcal · {{ $req->deliveredVersion->protein_grams }}g P)
                            </p>
                            <a href="{{ route('diet-plans.show', $req->dietPlan->slug) }}" class="inline-block font-bold text-gold-dark underline">
                                Open Meal Schedule, Food Swaps & Shopping List →
                            </a>
                        @else
                            <p class="text-graphite/70">Assigned Coach: {{ $req->assignedCoach?->name ?? 'Operations Queue' }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-graphite/70">No personalized diet requests yet.</p>
        @endif

        <div class="border-t border-graphite/10 pt-3 space-y-2 text-xs">
            <span class="font-bold uppercase text-graphite block">Entitled Training Video Library ({{ count($entitledVideoIds) }}/{{ $videos->count() }} Unlocked)</span>
            <div class="grid grid-cols-1 gap-2">
                @foreach ($videos->take(3) as $v)
                    <div class="flex items-center justify-between rounded-xs bg-paper px-3 py-2">
                        <span class="truncate pr-2 font-medium text-graphite">{{ $v->title }}</span>
                        @if (in_array($v->id, $entitledVideoIds, true))
                            <a href="{{ route('videos.watch', $v->slug) }}" class="shrink-0 font-bold text-gold-dark hover:underline">Watch →</a>
                        @else
                            <span class="shrink-0 text-[10px] uppercase text-graphite/50">Locked</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
