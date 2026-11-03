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

        {{-- Assigned Workout Program & Session Completion Logger + 12-Point Intake Form --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="workouts" x-show="tab === 'workouts'" x-cloak>
            {{-- Module 7: Assigned Workout Program & Completion Tracking --}}
            <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 7 · Custom Workout Assignments & Completion</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">My Assigned Workout Schedule</h2>
                </div>

                @forelse ($workoutAssignments as $wa)
                    <div class="rounded-xs border border-graphite/12 bg-paper p-4 space-y-3 text-xs">
                        <div class="flex items-center justify-between">
                            <strong class="text-sm text-graphite">{{ $wa->title_override ?? $wa->program?->title }}</strong>
                            <span class="rounded-xs bg-charcoal px-2.5 py-1 font-mono text-[11px] font-bold text-gold">
                                {{ $wa->completed_sessions_count }} Sessions Completed
                            </span>
                        </div>
                        @if ($wa->custom_guidance)
                            <p class="text-graphite/80 border-l-2 border-gold-dark pl-2.5"><strong>Coach Guidance:</strong> {{ $wa->custom_guidance }}</p>
                        @endif
                        @if (! empty($wa->scheduled_days))
                            <ul class="space-y-1 text-graphite/75">
                                @foreach ($wa->scheduled_days as $dayLine)
                                    <li>• {{ $dayLine }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($wa->last_session_log)
                            <p class="text-[11px] text-success font-semibold">
                                Last Logged: {{ $wa->last_session_log['day_label'] ?? '' }} ({{ $wa->last_session_log['duration_minutes'] ?? 60 }} min · RPE {{ $wa->last_session_log['rpe'] ?? 8 }}) — "{{ $wa->last_session_log['notes'] ?? '' }}"
                            </p>
                        @endif

                        <form method="POST" action="{{ route('account.coaching.workouts.log', $wa) }}" class="grid grid-cols-1 gap-2 border-t border-graphite/10 pt-3 sm:grid-cols-4">
                            @csrf
                            <input type="text" name="day_label" value="Day 1: Upper Strength" class="field-input text-xs py-1.5 sm:col-span-2" placeholder="Session / Day" required />
                            <input type="number" name="duration_minutes" value="60" min="10" max="240" class="field-input text-xs py-1.5" placeholder="Mins" required />
                            <input type="number" name="rpe" value="8" min="1" max="10" class="field-input text-xs py-1.5" placeholder="RPE (1-10)" required />
                            <input type="text" name="notes" placeholder="Top set weights & session notes..." class="field-input text-xs py-1.5 sm:col-span-3" />
                            <button type="submit" class="btn-light-primary rounded-xs px-3 py-1.5 text-[11px]">Log Workout</button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-graphite/70">No custom workout assignments yet. Your coach will assign your training split after reviewing your intake.</p>
                @endforelse
            </section>

            {{-- 12-Point Personalization Intake Form --}}
            <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">12-Point Personalization Intake · Encrypted Health Notes</span>
                        <h2 class="font-display text-2xl uppercase text-graphite">Coaching & Diet Intake Questionnaire</h2>
                    </div>
                    @if ($latestIntake)
                        <span class="rounded-xs px-2.5 py-1 text-[11px] font-bold uppercase {{ $latestIntake->needs_professional_review ? 'bg-danger/15 text-danger' : 'bg-success/15 text-success' }}">
                            Status: {{ str_replace('_', ' ', $latestIntake->review_status) }}
                        </span>
                    @endif
                </div>

                @if ($latestIntake && $latestIntake->needs_professional_review)
                    <div class="rounded-xs border border-danger/40 bg-danger/10 p-3 text-xs text-danger">
                        <strong>Professional Review Flag:</strong> {{ $latestIntake->review_flag_reason }}
                    </div>
                @endif

                <form method="POST" action="{{ route('account.coaching.intake.store') }}" class="space-y-3 text-xs">
                    @csrf
                    @if ($activeSubscription)
                        <input type="hidden" name="coaching_subscription_id" value="{{ $activeSubscription->id }}" />
                    @endif
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="col-span-2">
                            <label class="field-label text-xs">1. Primary Goal</label>
                            <input type="text" name="fitness_goal" value="{{ old('fitness_goal', $latestIntake?->fitness_goal ?? 'Body Recomposition') }}" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">2. Age</label>
                            <input type="number" name="age" value="{{ old('age', $latestIntake?->age ?? 29) }}" min="16" max="85" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">3. Height (cm)</label>
                            <input type="number" name="height_cm" value="{{ old('height_cm', $latestIntake?->height_cm ?? 178) }}" min="120" max="230" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">4. Weight (kg)</label>
                            <input type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg', $latestIntake?->weight_kg ?? 84.0) }}" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">5. Activity Level</label>
                            <input type="text" name="activity_level" value="{{ old('activity_level', $latestIntake?->activity_level ?? 'Moderately Active') }}" class="field-input text-xs py-1.5" required />
                        </div>
                        <div class="col-span-2">
                            <label class="field-label text-xs">6. Training Experience</label>
                            <input type="text" name="training_experience" value="{{ old('training_experience', $latestIntake?->training_experience ?? 'Intermediate (2+ years)') }}" class="field-input text-xs py-1.5" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="field-label text-xs">7. Pakistani / Household Food Preferences</label>
                            <input type="text" name="food_preferences" value="{{ old('food_preferences', $latestIntake?->food_preferences ?? 'Chicken tikka, eggs, whole-wheat chapati, basmati rice, daal, dahi') }}" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">8. Allergies & 9. Dietary Restrictions</label>
                            <input type="text" name="allergies" value="{{ old('allergies', $latestIntake?->allergies ?? 'None') }}" placeholder="Allergies" class="field-input text-xs py-1.5 mb-1.5" />
                            <input type="text" name="dietary_restrictions" value="{{ old('dietary_restrictions', $latestIntake?->dietary_restrictions ?? 'Halal only') }}" placeholder="Restrictions" class="field-input text-xs py-1.5" />
                        </div>
                        <div>
                            <label class="field-label text-xs">10. Equipment Availability</label>
                            <input type="text" name="equipment_availability" value="{{ old('equipment_availability', $latestIntake?->equipment_availability ?? 'Full Commercial Gym') }}" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">11. Preferred Weekly Schedule</label>
                            <input type="text" name="preferred_schedule" value="{{ old('preferred_schedule', $latestIntake?->preferred_schedule ?? '4 evenings/week (Mon/Tue/Thu/Fri)') }}" class="field-input text-xs py-1.5" required />
                        </div>
                    </div>

                    <div>
                        <label class="field-label text-xs">12. Optional Health / Injury Considerations (Encrypted at Rest)</label>
                        <input type="text" name="health_notes" placeholder="Any joint discomfort, medical history, or physician guidance..." class="field-input text-xs py-1.5" />
                    </div>

                    <label class="flex items-start gap-2 text-[11px] text-graphite/80">
                        <input type="checkbox" name="health_consent" value="1" checked class="mt-0.5" />
                        <span>I explicitly consent to BIN MALIK FITNESS storing my intake & health notes for coaching personalisation. I understand this is not a medical prescription.</span>
                    </label>

                    <button type="submit" class="btn-light-primary rounded-xs px-4 py-2.5 text-xs">
                        Submit / Update 12-Point Personalization Intake
                    </button>
                </form>
            </section>
        </div>

        {{-- Module 9 & 10: Weekly Check-In, Body Measurements & Visual Progress Tracker --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="progress" x-show="tab === 'progress'" x-cloak>
            <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 9 & 10 · Private Outcome Tracking & Coach Feedback</span>
                        <h2 class="font-display text-2xl uppercase text-graphite">Weight & Waist Progress Chart ({{ $checkIns->count() }} Check-Ins)</h2>
                    </div>
                    <span class="rounded-xs bg-paper px-2.5 py-1 text-[11px] font-semibold text-graphite">Private by Default</span>
                </div>

                @if ($checkIns->isNotEmpty())
                    @php
                        $chronological = $checkIns->sortBy('check_in_date')->values();
                        $firstCheckIn = $chronological->first();
                        $latestCheckIn = $chronological->last();
                        $weightDelta = round($latestCheckIn->weight_kg - $firstCheckIn->weight_kg, 1);
                        $waistDelta = ($latestCheckIn->waist_cm && $firstCheckIn->waist_cm)
                            ? round($latestCheckIn->waist_cm - $firstCheckIn->waist_cm, 1)
                            : null;
                    @endphp
                    <div class="grid grid-cols-3 gap-3 rounded-xs bg-charcoal p-4 text-xs text-ivory">
                        <div>
                            <span class="text-silver block">Starting → Latest Weight</span>
                            <strong class="font-mono text-base text-gold">{{ $firstCheckIn->weight_kg }} kg → {{ $latestCheckIn->weight_kg }} kg ({{ $weightDelta > 0 ? '+'.$weightDelta : $weightDelta }} kg)</strong>
                        </div>
                        <div>
                            <span class="text-silver block">Waist Circumference Delta</span>
                            <strong class="font-mono text-base text-ivory">{{ $latestCheckIn->waist_cm ?? '—' }} cm {{ $waistDelta !== null ? '('.($waistDelta > 0 ? '+'.$waistDelta : $waistDelta).' cm)' : '' }}</strong>
                        </div>
                        <div>
                            <span class="text-silver block">Avg Adherence & Energy</span>
                            <strong class="font-mono text-base text-gold">{{ round($checkIns->avg('diet_adherence_score'), 1) }}/10 · {{ round($checkIns->avg('energy_score'), 1) }}/10</strong>
                        </div>
                    </div>

                    {{-- Visual Bar Progression Chart --}}
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        @foreach ($chronological as $ci)
                            <div class="rounded-xs border border-graphite/12 bg-paper p-3 space-y-1 text-xs">
                                <div class="flex items-center justify-between font-mono text-[11px] text-graphite/70">
                                    <span>{{ $ci->check_in_date->format('d M Y') }}</span>
                                    <span class="font-bold text-gold-dark">{{ $ci-> workouts_completed_this_week }} workouts</span>
                                </div>
                                <div class="font-display text-2xl text-graphite">{{ $ci->weight_kg }} kg <span class="text-xs font-sans text-graphite/70">/ {{ $ci->waist_cm ?? '—' }} cm waist</span></div>
                                <p class="text-[11px] text-graphite/80">{{ $ci->client_notes }}</p>
                                @if ($ci->coach_feedback)
                                    <p class="rounded-xs bg-white p-2 text-[11px] text-graphite border-l-2 border-gold-dark">
                                        <strong>Coach Feedback:</strong> {{ $ci->coach_feedback }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-graphite/70">Submit your first weekly check-in on the right to start tracking weight, waist, and adherence trends.</p>
                @endif
            </section>

            {{-- Submit Weekly Check-In Form --}}
            <section class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                <h2 class="font-display text-2xl uppercase text-graphite">Submit Weekly Check-In & Measurements</h2>
                <form method="POST" action="{{ route('account.coaching.check-ins.store') }}" enctype="multipart/form-data" class="space-y-3 text-xs">
                    @csrf
                    @if ($activeSubscription)
                        <input type="hidden" name="coaching_subscription_id" value="{{ $activeSubscription->id }}" />
                    @endif
                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label class="field-label text-xs">Weight (kg)*</label>
                            <input type="number" step="0.1" name="weight_kg" value="83.4" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">Waist (cm)</label>
                            <input type="number" step="0.1" name="waist_cm" value="88.5" class="field-input text-xs py-1.5" />
                        </div>
                        <div>
                            <label class="field-label text-xs">Chest (cm)</label>
                            <input type="number" step="0.1" name="chest_cm" value="106.0" class="field-input text-xs py-1.5" />
                        </div>
                        <div>
                            <label class="field-label text-xs">Adherence (1-10)</label>
                            <input type="number" name="diet_adherence_score" value="9" min="1" max="10" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">Energy (1-10)</label>
                            <input type="number" name="energy_score" value="9" min="1" max="10" class="field-input text-xs py-1.5" required />
                        </div>
                        <div>
                            <label class="field-label text-xs">Workouts Done</label>
                            <input type="number" name="workouts_completed_this_week" value="4" min="0" max="14" class="field-input text-xs py-1.5" required />
                        </div>
                    </div>
                    <div>
                        <label class="field-label text-xs">Biofeedback, Sleep & Training Notes</label>
                        <textarea name="client_notes" rows="2" placeholder="Digestion, sleep quality, strength progression..." class="field-input text-xs"></textarea>
                    </div>
                    <div>
                        <label class="field-label text-xs">Optional Private Progress Photo (Stored Outside /public)</label>
                        <input type="file" name="progress_photo" accept=".jpg,.jpeg,.png,.webp" class="field-input text-xs py-1.5" />
                    </div>
                    <label class="flex items-start gap-2 text-[11px] text-graphite/80">
                        <input type="checkbox" name="photo_storage_consent" value="1" checked class="mt-0.5" />
                        <span>Consent to store progress photo privately for my assigned coach only (Never displayed publicly unless separately consented).</span>
                    </label>
                    <button type="submit" class="btn-light-primary w-full rounded-xs px-4 py-2.5 text-xs">
                        Record Weekly Check-In
                    </button>
                </form>
            </section>
        </div>

        {{-- Module 11: Consultation Bookings & Secure Customer-Coach Messaging --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="sessions" x-show="tab === 'sessions'" x-cloak>
            <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 11 · 1-on-1 Consultation Booking & History</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Coach Consultations ({{ $consultations->count() }})</h2>
                </div>

                @if ($consultations->isNotEmpty())
                    <div class="space-y-2 text-xs">
                        @foreach ($consultations as $con)
                            <div class="flex items-center justify-between rounded-xs bg-paper p-3">
                                <div>
                                    <strong class="text-graphite">{{ $con->topic }}</strong>
                                    <span class="block text-[11px] text-graphite/70">
                                        {{ $con->scheduled_at->format('d M Y, H:i') }} PKT · {{ strtoupper(str_replace('_', ' ', $con->channel)) }}
                                        @if ($con->meeting_reference) · {{ $con->meeting_reference }} @endif
                                    </span>
                                </div>
                                <span class="rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $con->status }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('account.coaching.consultations.store') }}" class="grid grid-cols-1 gap-2.5 border-t border-graphite/10 pt-3 sm:grid-cols-2 text-xs">
                    @csrf
                    <div>
                        <label class="field-label text-xs">Preferred Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ now()->addDays(4)->format('Y-m-d\TH:i') }}" class="field-input text-xs py-1.5" required />
                    </div>
                    <div>
                        <label class="field-label text-xs">Channel</label>
                        <select name="channel" class="field-input text-xs py-1.5">
                            <option value="whatsapp_video">WhatsApp Video Call</option>
                            <option value="zoom">Zoom Video Session</option>
                            <option value="in_person_gym">In-Person Facility Consultation</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label text-xs">Consultation Topic</label>
                        <input type="text" name="topic" placeholder="e.g. Form review & next mesocycle targets" class="field-input text-xs py-1.5" required />
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs">Request 1-on-1 Consultation</button>
                    </div>
                </form>
            </section>

            {{-- Secure Customer-Coach Messaging --}}
            <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Direct Portal Thread · Scoped to Assigned Coach</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">Secure Coach Messaging ({{ $coachingMessages->count() }})</h2>
                </div>

                <div class="max-h-56 overflow-y-auto space-y-2 rounded-xs bg-paper p-3 text-xs">
                    @forelse ($coachingMessages as $msg)
                        <div class="rounded-xs {{ $msg->sender_id === $user->id ? 'bg-white border border-graphite/15' : 'bg-charcoal text-ivory' }} p-2.5">
                            <div class="flex items-center justify-between text-[10px] opacity-75">
                                <strong>{{ $msg->sender?->name ?? 'User' }}</strong>
                                <span>{{ $msg->created_at?->format('d M H:i') }}</span>
                            </div>
                            <p class="mt-1">{{ $msg->body }}</p>
                        </div>
                    @empty
                        <p class="text-graphite/70">No messages yet. Send a question to your assigned coach below.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('account.coaching.messages.store') }}" class="flex gap-2 text-xs">
                    @csrf
                    @if ($activeSubscription)
                        <input type="hidden" name="coaching_subscription_id" value="{{ $activeSubscription->id }}" />
                    @endif
                    <input type="text" name="body" placeholder="Ask your assigned coach about form, macros, or food swaps..." class="field-input text-xs py-2 flex-1" required />
                    <button type="submit" class="btn-light-primary shrink-0 rounded-xs px-4 py-2 text-xs">Send Message</button>
                </form>
            </section>
        </div>
    </div>

    {{-- MODULE 11 & 12: Stage 5 Physical Gym Membership Card, Signed QR Pass, Attendance History & Class Bookings --}}
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

    {{-- MODULE 3, 14 & 15: My Supplement Orders, Invoices & Wishlist --}}
    <div id="my-orders-section" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="orders" x-show="tab === 'orders'" x-cloak>
        <section class="lg:col-span-8 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl uppercase text-graphite">My Supplement Orders & PKR Invoices</h2>
                <a href="{{ route('shop.index') }}" class="text-xs font-bold uppercase text-gold-dark hover:underline">Shop Supplements →</a>
            </div>
            @if ($orders->isEmpty())
                <p class="text-xs text-graphite/70">You have not placed any supplement orders yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-graphite/15 text-gold-dark uppercase">
                                <th class="py-2 pr-3">Order #</th>
                                <th class="py-2 px-3">Date</th>
                                <th class="py-2 px-3">Status & Payment</th>
                                <th class="py-2 px-3">Total (PKR)</th>
                                <th class="py-2 pl-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-graphite/10">
                            @foreach ($orders as $ord)
                                <tr>
                                    <td class="py-2.5 pr-3 font-mono font-bold text-graphite">{{ $ord->order_number }}</td>
                                    <td class="py-2.5 px-3">{{ $ord->placed_at?->format('d M Y') }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-bold uppercase">{{ str_replace('_', ' ', $ord->status) }}</span>
                                        <span class="block text-[11px] text-graphite/65">Payment: {{ str_replace('_', ' ', $ord->payment_status) }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 font-bold">{{ Money::format($ord->total_paisa) }}</td>
                                    <td class="py-2.5 pl-3 space-x-2">
                                        <a href="{{ route('orders.confirmation', ['orderNumber' => $ord->order_number, 'token' => $ord->access_token]) }}" class="font-semibold text-gold-dark hover:underline">Manage</a>
                                        <a href="{{ route('orders.invoice', ['orderNumber' => $ord->order_number, 'token' => $ord->access_token]) }}" class="font-semibold text-graphite hover:underline">Invoice</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="lg:col-span-4 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <h2 class="font-display text-2xl uppercase text-graphite">Saved Wishlist ({{ $wishlistItems->count() }})</h2>
            @if ($wishlistItems->isEmpty())
                <p class="text-xs text-graphite/70">Save supplements from the shop to track flavors and restocks here.</p>
            @else
                <div class="space-y-2.5 text-xs">
                    @foreach ($wishlistItems as $w)
                        @if ($w->product)
                            <div class="flex items-center justify-between gap-2 rounded-xs bg-paper p-3">
                                <div>
                                    <a href="{{ route('products.show', $w->product->slug) }}" class="font-bold text-graphite hover:text-gold-dark">{{ $w->product->name }}</a>
                                    <span class="block text-gold-dark font-semibold">{{ $w->product->defaultVariant ? Money::format($w->product->defaultVariant->price) : '' }}</span>
                                </div>
                                <form method="POST" action="{{ route('wishlist.toggle') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $w->product_id }}" />
                                    <button type="submit" class="text-danger font-semibold hover:underline cursor-pointer">Remove</button>
                                </form>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- MODULE 2: My Profile & Training Parameters --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="profile" x-show="tab === 'profile'" x-cloak>
        <section id="profile-form" class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl uppercase text-graphite">My Profile & Training Parameters</h2>
                <x-ui.badge tone="light">Persisted in MySQL</x-ui.badge>
            </div>

            <form method="POST" action="{{ route('account.profile.update') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <label for="prof-name" class="field-label text-xs">Full Name</label>
                    <input id="prof-name" type="text" name="name" value="{{ old('name', $user->name) }}" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="prof-phone" class="field-label text-xs">Mobile Number (Pakistan)</label>
                    <input id="prof-phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="prof-goal" class="field-label text-xs">Primary Fitness Goal</label>
                    <input id="prof-goal" type="text" name="fitness_goal" value="{{ old('fitness_goal', $profile?->fitness_goal ?? 'Body Recomposition & Strength') }}" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="prof-equip" class="field-label text-xs">Equipment Access</label>
                    <input id="prof-equip" type="text" name="equipment_access" value="{{ old('equipment_access', $profile?->equipment_access) }}" class="field-input text-xs" placeholder="Full Commercial Gym / Home Dumbbells" />
                </div>
                <div>
                    <label for="prof-height" class="field-label text-xs">Height (cm)</label>
                    <input id="prof-height" type="number" name="height_cm" value="{{ old('height_cm', $profile?->height_cm) }}" class="field-input text-xs" />
                </div>
                <div>
                    <label for="prof-weight" class="field-label text-xs">Current Weight (kg)</label>
                    <input id="prof-weight" type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg', $profile?->weight_kg) }}" class="field-input text-xs" />
                </div>
                <div class="sm:col-span-2">
                    <x-ui.button type="submit" variant="graphite" size="sm">Save Profile & Fitness Metrics</x-ui.button>
                </div>
            </form>
        </section>

        {{-- Assigned Coaches & Private File Upload --}}
        <section id="private-uploads" class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-2xl uppercase text-graphite">Private Check-In & File Upload</h2>
                <x-ui.badge tone="gold">Private Disk</x-ui.badge>
            </div>
            <p class="text-xs text-graphite/75">
                Files are stored outside <code class="font-mono">/public</code> and can only be accessed by you, an Admin, or your assigned coach.
            </p>

            <form method="POST" action="{{ route('account.private-files.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label for="file-cat" class="field-label text-xs">Document / Photo Category</label>
                    <select id="file-cat" name="category" class="field-input text-xs">
                        <option value="progress_photo">Private Check-In Progress Photo</option>
                        <option value="intake_document">Bloodwork / Intake Attachment (PDF/Image)</option>
                        <option value="payment_receipt">Bank Transfer Receipt</option>
                    </select>
                </div>
                <div>
                    <label for="file-input" class="field-label text-xs">Select File (JPG, PNG, WEBP, PDF · Max 5 MB)</label>
                    <input id="file-input" type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="field-input text-xs" required />
                </div>
                <label class="flex items-start gap-2 text-xs text-graphite/80 cursor-pointer">
                    <input type="checkbox" name="consent_granted" value="1" class="mt-0.5 accent-graphite" required />
                    <span>I consent to storing this file privately for my coaching/order review. It will never be published publicly without a separate consent agreement.</span>
                </label>
                <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">
                    Upload to Private Storage
                </x-ui.button>
            </form>

            @if ($user->privateFiles->isNotEmpty())
                <div class="border-t border-graphite/10 pt-3 space-y-2 text-xs">
                    <h3 class="font-bold uppercase text-gold-dark">My Uploaded Private Files</h3>
                    @foreach ($user->privateFiles as $pf)
                        <div class="flex items-center justify-between rounded-xs bg-paper px-3 py-2">
                            <span class="truncate pr-2">{{ $pf->original_name }} ({{ $pf->category }})</span>
                            <a href="{{ route('private-files.download', $pf) }}" class="font-semibold text-gold-dark hover:underline shrink-0">Download →</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- MODULE 4: Saved Addresses & MODULE 16: Password Security --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="addresses" x-show="tab === 'addresses'" x-cloak>
        <section id="addresses-section" class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <h2 class="font-display text-2xl uppercase text-graphite">Saved Delivery Addresses (Pakistan)</h2>

            <div class="space-y-2 text-xs">
                @foreach ($user->addresses as $addr)
                    <div class="flex items-center justify-between gap-3 rounded-xs border border-graphite/12 bg-paper p-3.5">
                        <div>
                            <strong class="uppercase text-gold-dark">{{ $addr->label }}</strong> · <span class="font-semibold">{{ $addr->recipient_name }} ({{ $addr->phone }})</span>
                            <p class="text-graphite/75">{{ $addr->line1 }}, {{ $addr->area ? $addr->area.', ' : '' }}{{ $addr->city }}, {{ $addr->province }}</p>
                        </div>
                        <form method="POST" action="{{ route('account.addresses.destroy', $addr) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-danger font-semibold hover:underline cursor-pointer">Delete</button>
                        </form>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('account.addresses.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3 border-t border-graphite/10 pt-4">
                @csrf
                <div>
                    <label for="addr-label" class="field-label text-xs">Label</label>
                    <input id="addr-label" type="text" name="label" value="Home" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="addr-rec" class="field-label text-xs">Recipient Name</label>
                    <input id="addr-rec" type="text" name="recipient_name" value="{{ $user->name }}" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="addr-phone" class="field-label text-xs">Phone</label>
                    <input id="addr-phone" type="tel" name="phone" value="{{ $user->phone ?? '0300-1234567' }}" class="field-input text-xs" required />
                </div>
                <div class="sm:col-span-2">
                    <label for="addr-line1" class="field-label text-xs">Street Address / House / Sector</label>
                    <input id="addr-line1" type="text" name="line1" class="field-input text-xs" placeholder="House 10, Street 4, DHA Phase 5" required />
                </div>
                <div>
                    <label for="addr-city" class="field-label text-xs">City</label>
                    <input id="addr-city" type="text" name="city" value="Lahore" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="addr-prov" class="field-label text-xs">Province</label>
                    <select id="addr-prov" name="province" class="field-input text-xs">
                        <option>Punjab</option>
                        <option>Sindh</option>
                        <option>Khyber Pakhtunkhwa</option>
                        <option>Islamabad Capital Territory</option>
                        <option>Balochistan</option>
                    </select>
                </div>
                <div class="sm:col-span-2 flex items-end">
                    <x-ui.button type="submit" variant="graphite" size="sm">Add Delivery Address</x-ui.button>
                </div>
            </form>
        </section>

        <section id="security-settings" class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <h2 class="font-display text-2xl uppercase text-graphite">Change Account Password</h2>
            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label for="cur-pass" class="field-label text-xs">Current Password</label>
                    <input id="cur-pass" type="password" name="current_password" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="new-pass" class="field-label text-xs">New Password (Min 8 chars)</label>
                    <input id="new-pass" type="password" name="password" class="field-input text-xs" required />
                </div>
                <div>
                    <label for="new-pass-conf" class="field-label text-xs">Confirm New Password</label>
                    <input id="new-pass-conf" type="password" name="password_confirmation" class="field-input text-xs" required />
                </div>
                <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">Update Password</x-ui.button>
            </form>
        </section>
    </div>

    {{-- STAGE 6 MODULE 15 & 16: Customer Support Tickets & Privacy / Consent Governance --}}
    <div id="support-tickets" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="support" x-show="tab === 'support'" x-cloak>
        <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Customer Help Desk &amp; Escalations</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">My Support Tickets ({{ $supportTickets->count() }})</h2>
                </div>
                <x-ui.badge tone="gold">Direct Staff Support</x-ui.badge>
            </div>

            <div class="space-y-3 text-xs">
                @forelse ($supportTickets as $ticket)
                    <div class="rounded-xs border border-graphite/15 bg-paper p-4 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono font-bold text-gold-dark">{{ $ticket->ticket_number }}</span>
                                <span class="ml-1 rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $ticket->category }}</span>
                                <strong class="block mt-1 text-sm text-graphite">{{ $ticket->subject }}</strong>
                            </div>
                            <x-ui.badge :tone="$ticket->status === 'resolved' ? 'success' : 'gold'">{{ $ticket->status }}</x-ui.badge>
                        </div>

                        <div class="space-y-1.5 rounded-xs bg-white p-2.5 border border-graphite/10">
                            @foreach ($ticket->messages as $msg)
                                <div class="rounded-xs bg-paper p-2">
                                    <div class="flex items-center justify-between text-[10px] text-graphite/65">
                                        <strong>{{ $msg->sender?->name }}</strong>
                                        <span>{{ $msg->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-0.5 text-graphite">{{ $msg->message }}</p>
                                </div>
                            @endforeach
                        </div>

                        <form method="POST" action="{{ route('account.support-tickets.reply', $ticket) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="text" name="message" placeholder="Add a reply to this support ticket..." class="field-input py-1.5 text-xs flex-1" required />
                            <button type="submit" class="rounded-xs bg-charcoal px-3 py-1.5 font-bold uppercase text-gold cursor-pointer">Reply</button>
                        </form>
                    </div>
                @empty
                    <p class="rounded-xs bg-paper p-4 text-graphite/70">You have no open support tickets. Submit a question below if you need help with an order, diet plan, or coaching subscription.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('account.support-tickets.store') }}" class="rounded-xs bg-paper p-4 space-y-2.5 text-xs border-t border-graphite/10">
                @csrf
                <strong class="uppercase text-graphite block">Open New Support Ticket</strong>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <select name="category" class="field-input py-1.5 text-xs">
                        <option value="coaching">Online Coaching</option>
                        <option value="order">Supplement Order</option>
                        <option value="diet">Digital Diet Plan</option>
                        <option value="gym">Gym Membership</option>
                        <option value="billing">Billing &amp; Payment</option>
                    </select>
                    <select name="priority" class="field-input py-1.5 text-xs">
                        <option value="normal">Normal Priority</option>
                        <option value="high">High Priority</option>
                    </select>
                    <select name="order_id" class="field-input py-1.5 text-xs">
                        <option value="">Optional: Link Order</option>
                        @foreach ($orders as $ordOpt)
                            <option value="{{ $ordOpt->id }}">{{ $ordOpt->order_number }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="text" name="subject" placeholder="Brief subject of your enquiry..." class="field-input py-1.5 text-xs" required />
                <textarea name="message" rows="2" placeholder="Describe your question or issue..." class="field-input py-1.5 text-xs" required></textarea>
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-2 text-xs w-full">Submit Support Ticket</button>
            </form>
        </section>

        <section id="privacy-governance" class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-graphite/10 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gold-dark">Module 16 · Privacy, Consents &amp; Data Export</span>
                    <h2 class="font-display text-2xl uppercase text-graphite">My Recorded Consents &amp; Data Rights</h2>
                </div>
                <x-ui.badge tone="light">Audited</x-ui.badge>
            </div>

            <div class="space-y-2 text-xs">
                <strong class="uppercase text-gold-dark block">Active &amp; Historical Consent Records ({{ $consentRecords->count() }})</strong>
                @foreach ($consentRecords as $cr)
                    <div class="flex items-center justify-between rounded-xs border border-graphite/10 bg-paper px-3 py-2">
                        <div>
                            <strong class="font-mono text-graphite">{{ $cr->consent_type }}</strong> ({{ $cr->version }})
                            <span class="block text-[10px] text-graphite/65">Granted {{ $cr->granted_at?->format('d M Y') }}</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase {{ $cr->withdrawn_at ? 'text-danger' : 'text-success' }}">
                            {{ $cr->withdrawn_at ? 'Withdrawn' : 'Active' }}
                        </span>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('account.privacy-requests.store') }}" class="rounded-xs bg-paper p-3.5 space-y-2 text-xs">
                @csrf
                <strong class="uppercase text-graphite block">Submit Privacy / Data Export / Consent Withdrawal Request</strong>
                <select name="request_type" class="field-input py-1.5 text-xs">
                    <option value="data_export">Portable Personal &amp; Training Data Export</option>
                    <option value="consent_withdrawal">Withdraw a Specific Consent Record</option>
                    <option value="account_deletion_enquiry">Account Deletion / Anonymisation Enquiry</option>
                </select>
                <select name="consent_record_id" class="field-input py-1.5 text-xs">
                    <option value="">Optional: Select Consent Record to Withdraw...</option>
                    @foreach ($consentRecords->whereNull('withdrawn_at') as $activeCr)
                        <option value="{{ $activeCr->id }}">Consent #{{ $activeCr->id }} ({{ $activeCr->consent_type }})</option>
                    @endforeach
                </select>
                <input type="text" name="customer_notes" placeholder="Notes or reason for request..." class="field-input py-1.5 text-xs" />
                <button type="submit" class="btn-light-primary rounded-xs px-4 py-1.5 text-xs w-full">Submit Privacy Request</button>
            </form>

            @if ($privacyRequests->isNotEmpty())
                <div class="space-y-1.5 text-xs border-t border-graphite/10 pt-3">
                    <strong class="uppercase text-graphite block">My Privacy Request History</strong>
                    @foreach ($privacyRequests as $pr)
                        <div class="rounded-xs bg-paper px-3 py-2 flex items-center justify-between">
                            <span><strong>{{ $pr->request_type }}</strong> — {{ $pr->customer_notes }}</span>
                            <x-ui.badge :tone="$pr->status === 'completed' ? 'success' : 'gold'">{{ $pr->status }}</x-ui.badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layouts.dashboard>
