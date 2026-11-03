<x-layouts.public
    title="1-on-1 Online Fitness Coaching, Diet Plans & Training Library — BIN MALIK FITNESS"
    description="Structured online strength and nutrition coaching in Pakistan with 12-point personalization intake, South Asian meal architecture, workout programs, and entitled video library."
>
    <section class="section-y border-b border-white/10 bg-ink">
        <div class="container-site space-y-12">
            @if (session('status'))
                <div class="rounded-sm border border-gold/40 bg-gold/10 px-4 py-3 text-sm font-medium text-ivory">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-ui.section-heading
                    eyebrow="Primary Platform Objective · 1-to-1 Coaching"
                    title="Online Coaching & Nutrition Hub"
                    subtitle="Every coaching client goes through a 12-point personalization intake, staff review, custom training and Pakistani-household meal assignment, and weekly check-in feedback."
                    surface="dark"
                />
                <x-ui.demo-flag label="Stage 4 Live Coaching & Programs" />
            </div>

            {{-- 4-Step Coaching Workflow --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['step' => '01', 'title' => 'Select Tier & Verify Payment', 'desc' => 'Choose 8, 12, or 16 weeks. Manual bank transfer is verified by staff before subscription activation.'],
                    ['step' => '02', 'title' => '12-Point Intake & Health Consent', 'desc' => 'Submit goal, age, height, weight, schedule, equipment, allergies, and encrypted health notes with explicit consent.'],
                    ['step' => '03', 'title' => 'Coach Assignment & Custom Plan', 'desc' => 'A qualified coach prepares your training block and Pakistani-household meal schedule with food swaps.'],
                    ['step' => '04', 'title' => 'Weekly Check-Ins & Video Library', 'desc' => 'Log weights, measurements, and workouts; message your coach and stream entitled technique videos.'],
                ] as $s)
                    <div class="rounded-sm border border-white/12 bg-charcoal p-5 space-y-2">
                        <span class="font-display text-3xl text-gold">{{ $s['step'] }}</span>
                        <h3 class="font-display text-xl text-ivory">{{ $s['title'] }}</h3>
                        <p class="text-xs leading-relaxed text-silver">{{ $s['desc'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Coaching Packages Grid --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($packages as $package)
                    <x-coaching.package-card :package="$package" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- 7-Category Diet Plans Catalog --}}
    <section id="diet-plans" class="section-y border-b border-white/10 bg-charcoal">
        <div class="container-site space-y-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-ui.section-heading
                    eyebrow="7 Diet-Plan Categories · Free, Paid & Personalised"
                    title="South Asian Diet Plans & Meal Blueprints"
                    subtitle="Built around everyday Pakistani kitchen staples (chapati, basmati rice, daal, chicken, beef, eggs, dahi) with gram + household portion measures, approved food swaps, and weekly grocery lists."
                    surface="dark"
                />
            </div>

            {{-- Category Filter Pills --}}
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('coaching.index') }}#diet-plans"
                    class="rounded-xs px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition {{ $selectedDietCategory === '' ? 'bg-gold text-ink' : 'border border-white/15 bg-ink text-silver hover:border-gold hover:text-ivory' }}"
                >
                    All Categories (7)
                </a>
                @foreach ($dietCategories as $cat)
                    <a
                        href="{{ route('coaching.index', ['diet_category' => $cat]) }}#diet-plans"
                        class="rounded-xs px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition {{ $selectedDietCategory === $cat ? 'bg-gold text-ink' : 'border border-white/15 bg-ink text-silver hover:border-gold hover:text-ivory' }}"
                    >
                        {{ $cat }}
                    </a>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @forelse ($dietPlans as $plan)
                    <x-programs.diet-card :plan="$plan" />
                @empty
                    <div class="col-span-3 rounded-sm border border-white/12 bg-ink p-8 text-center text-sm text-silver">
                        No diet plans found in this category.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- 7-Category Workout Programs Catalog --}}
    <section id="workout-programs" class="section-y border-b border-white/10 bg-ink">
        <div class="container-site space-y-8">
            <x-ui.section-heading
                eyebrow="7 Workout Categories · Progressive Overload Templates"
                title="Structured Workout Programs"
                subtitle="Periodized gym and home training blocks with RPE guidance, warm-up standards, and exercise progression."
                surface="dark"
            />

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('coaching.index') }}#workout-programs"
                    class="rounded-xs px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition {{ $selectedWorkoutCategory === '' ? 'bg-gold text-ink' : 'border border-white/15 bg-charcoal text-silver hover:border-gold hover:text-ivory' }}"
                >
                    All Workout Categories (7)
                </a>
                @foreach ($workoutCategories as $wCat)
                    <a
                        href="{{ route('coaching.index', ['workout_category' => $wCat]) }}#workout-programs"
                        class="rounded-xs px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition {{ $selectedWorkoutCategory === $wCat ? 'bg-gold text-ink' : 'border border-white/15 bg-charcoal text-silver hover:border-gold hover:text-ivory' }}"
                    >
                        {{ $wCat }}
                    </a>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($workoutPrograms as $wp)
                    <article class="flex flex-col justify-between rounded-sm border border-white/12 bg-charcoal p-6 space-y-5">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="eyebrow text-gold">{{ $wp->category }}</span>
                                <span class="rounded-xs border border-white/15 bg-ink px-2 py-0.5 text-[11px] text-silver">
                                    {{ $wp->duration_weeks }} Wks · {{ $wp->days_per_week }}d/wk
                                </span>
                            </div>
                            <h3 class="font-display text-2xl text-ivory">{{ $wp->title }}</h3>
                            <p class="text-xs leading-relaxed text-silver">{{ $wp->summary }}</p>
                            <p class="text-[11px] text-silver/80">
                                <strong class="text-ivory">Equipment:</strong> {{ $wp->equipment_required }} · <strong class="text-ivory">Level:</strong> {{ $wp->level }}
                            </p>
                            @if (! empty($wp->structure))
                                <ul class="space-y-1 border-t border-white/10 pt-3 text-[11px] text-silver">
                                    @foreach (array_slice($wp->structure, 0, 4) as $dayLine)
                                        <li class="truncate">• {{ $dayLine }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div class="space-y-3 border-t border-white/10 pt-4">
                            <div class="flex items-center justify-between">
                                <x-ui.price :paisa="$wp->price" surface="dark" size="md" />
                                <span class="text-[11px] text-silver">One-time entitlement</span>
                            </div>
                            @auth
                                <form method="POST" action="{{ route('workout-programs.purchase', $wp->slug) }}">
                                    @csrf
                                    <input type="hidden" name="payment_method" value="bank_transfer" />
                                    <button type="submit" class="w-full rounded-xs bg-gold px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-ink transition hover:bg-gold/90">
                                        Purchase Workout Program
                                    </button>
                                </form>
                            @else
                                <x-ui.button :href="route('login')" variant="outline-light" size="sm" class="w-full">
                                    Sign In to Purchase Program
                                </x-ui.button>
                            @endauth
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Free & Entitled Training Video Library --}}
    <section id="video-library" class="section-y bg-charcoal">
        <div class="container-site space-y-8">
            <x-ui.section-heading
                eyebrow="Server-Side Entitled Media · Free Previews & Member Masterclasses"
                title="Technique & Coaching Video Library"
                subtitle="Free technical previews are open to all visitors. Member-only masterclasses enforce server-side entitlement checks and issue signed external playback tokens."
                surface="dark"
            />

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($videos as $video)
                    @php
                        $canWatch = in_array($video->id, $entitledVideoIds, true);
                    @endphp
                    <article class="flex flex-col justify-between overflow-hidden rounded-sm border {{ $video->access === 'entitled' ? 'border-gold/50' : 'border-white/12' }} bg-ink">
                        <div class="p-6 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="eyebrow text-gold">{{ $video->category }}</span>
                                @if ($video->access === 'public')
                                    <x-ui.badge tone="gold">Free Preview</x-ui.badge>
                                @elseif ($canWatch)
                                    <x-ui.badge tone="gold">Entitled · Unlocked</x-ui.badge>
                                @else
                                    <x-ui.badge tone="dark">Members Only · Locked</x-ui.badge>
                                @endif
                            </div>
                            <h3 class="font-display text-2xl text-ivory">{{ $video->title }}</h3>
                            <p class="text-xs leading-relaxed text-silver">{{ $video->summary }}</p>
                            <p class="text-[11px] text-silver/80">{{ $video->coach_note }} · Duration: {{ $video->formattedDuration() }}</p>
                        </div>

                        <div class="border-t border-white/10 bg-charcoal/60 px-6 py-4">
                            @if ($canWatch)
                                <a
                                    href="{{ route('videos.watch', $video->slug) }}"
                                    class="inline-flex w-full items-center justify-center rounded-xs bg-gold px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-ink transition hover:bg-gold/90"
                                >
                                    Watch Training Video ({{ $video->formattedDuration() }}) →
                                </a>
                            @else
                                <a
                                    href="{{ route('coaching.show', '12-week-complete-recomposition') }}"
                                    class="inline-flex w-full items-center justify-center rounded-xs border border-white/20 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-ivory transition hover:border-gold hover:text-gold"
                                >
                                    Requires Active Coaching Entitlement
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.public>
