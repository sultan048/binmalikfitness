@php
    $seoService = app(\App\Domain\Platform\SeoAndStructuredDataService::class);
    $homeSchemas = [
        $seoService->organizationAndHealthClubSchema(),
    ];
    if (isset($faqs) && $faqs->isNotEmpty()) {
        $homeSchemas[] = $seoService->faqPageSchema($faqs);
    }
@endphp

<x-layouts.public
    title="BIN MALIK FITNESS — 1-on-1 Online Coaching, Supplements & Strength Gym in Pakistan"
    description="Disciplined 1-on-1 online fitness coaching, Pakistani-kitchen nutrition plans, batch-tracked supplements in PKR, and physical strength training."
    :canonical="route('home')"
    :json-ld-schemas="$homeSchemas"
>
    {{-- SECTION 2, 3, 4: Hero with strong authentic training photograph, Main CTA (Start Online Coaching), Secondary CTA (Shop Supplements) --}}
    <section aria-labelledby="hero-heading" class="relative overflow-hidden border-b border-white/10 bg-ink">
        <div class="absolute inset-0">
            <picture>
                <source
                    type="image/webp"
                    srcset="/images/demo/hero-gym-768.webp 768w, /images/demo/hero-gym-1280.webp 1280w, /images/demo/hero-gym-1376.webp 1376w"
                    sizes="100vw"
                />
                <img
                    src="/images/demo/hero-gym-1280.jpg"
                    alt="Loaded barbell on a squat rack inside a dark strength gym"
                    fetchpriority="high"
                    width="1376"
                    height="768"
                    class="h-full w-full object-cover object-right opacity-55"
                />
            </picture>
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-ink/35"></div>
        </div>

        <div class="container-site relative py-20 sm:py-28 lg:py-34">
            <div class="max-w-2xl space-y-6">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="h-0.5 w-8 bg-gold" aria-hidden="true"></span>
                    <span class="eyebrow text-gold">Pakistan · Online Coaching · Supplements · Strength Gym</span>
                    <x-ui.demo-flag label="Demo Photography" />
                </div>

                <h1 id="hero-heading" class="display-xl text-ivory">
                    Built Through Discipline. Coached With Precision.
                </h1>

                <p class="max-w-xl text-base sm:text-lg leading-relaxed text-silver">
                    1-on-1 online training and Pakistani-kitchen nutrition systems engineered for measurable strength and body recomposition—backed by batch-verified sports supplements and an authentic barbell facility.
                </p>

                {{-- Section 3 (Main CTA) & Section 4 (Secondary CTA) --}}
                <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center">
                    <x-ui.button :href="route('coaching.index')" variant="gold" size="lg">
                        Start Online Coaching
                    </x-ui.button>
                    <x-ui.button :href="route('shop.index')" variant="outline-light" size="lg">
                        Shop Supplements
                    </x-ui.button>
                </div>

                <dl class="grid grid-cols-3 gap-4 border-t border-white/15 pt-6 text-xs sm:gap-8">
                    <div>
                        <dt class="text-silver uppercase tracking-wider">Primary Focus</dt>
                        <dd class="mt-1 font-display text-2xl text-ivory">1-on-1 Coaching</dd>
                    </div>
                    <div>
                        <dt class="text-silver uppercase tracking-wider">Supplement Supply</dt>
                        <dd class="mt-1 font-display text-2xl text-ivory">Lot & Expiry Tracked</dd>
                    </div>
                    <div>
                        <dt class="text-silver uppercase tracking-wider">Currency & Fulfilment</dt>
                        <dd class="mt-1 font-display text-2xl text-gold">PKR · Nationwide</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- SECTION 5: Three Primary Routes — Gym, Coaching, Supplements --}}
    <section aria-labelledby="routes-heading" class="section-y border-b border-white/10 bg-ink">
        <div class="container-site space-y-12">
            <x-ui.section-heading
                eyebrow="Four Integrated Business Lines · Three Core Entry Routes"
                title="Choose Your Discipline"
                subtitle="One brand architecture with dedicated journeys for online coaching, batch-tracked supplements, and physical gym training."
                surface="dark"
            />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Route 1: Online Coaching (Primary Conversion) --}}
                <article class="group relative flex flex-col justify-between overflow-hidden rounded-sm border border-gold/60 bg-charcoal">
                    <div class="aspect-[4/3] overflow-hidden bg-ink">
                        <img
                            src="/images/demo/route-coaching-800.webp"
                            alt="Training logbook and progress tracking on a gym bench"
                            loading="lazy"
                            width="800"
                            height="1071"
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        />
                    </div>
                    <div class="flex flex-1 flex-col justify-between p-6 sm:p-7">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="eyebrow text-gold">01 · Primary Objective</span>
                                <x-ui.badge tone="gold">Most Selected</x-ui.badge>
                            </div>
                            <h3 class="mt-2 font-display text-3xl text-ivory">Online Fitness Coaching</h3>
                            <p class="mt-2 text-sm leading-relaxed text-silver">
                                Structured 12-point intake, customized training blocks, South Asian nutrition plans, and weekly coach check-ins.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-white/10">
                            <x-ui.button :href="route('coaching.index')" variant="gold" size="sm" class="w-full">
                                Explore Coaching Programs
                            </x-ui.button>
                        </div>
                    </div>
                </article>

                {{-- Route 2: Supplements (Second Priority) --}}
                <article class="group relative flex flex-col justify-between overflow-hidden rounded-sm border border-white/12 bg-charcoal">
                    <div class="aspect-[4/3] overflow-hidden bg-ink">
                        <img
                            src="/images/demo/route-supplements-800.webp"
                            alt="Unbranded matte black supplement container and shaker"
                            loading="lazy"
                            width="800"
                            height="1071"
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        />
                    </div>
                    <div class="flex flex-1 flex-col justify-between p-6 sm:p-7">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="eyebrow text-gold">02 · Ecommerce Storefront</span>
                                <x-ui.badge tone="dark">COD & Bank Transfer</x-ui.badge>
                            </div>
                            <h3 class="mt-2 font-display text-3xl text-ivory">Supplements & Accessories</h3>
                            <p class="mt-2 text-sm leading-relaxed text-silver">
                                Whey protein, creatine, pre-workout, and heavy-duty lifting gear with transparent batch, expiry, and importer records.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-white/10">
                            <x-ui.button :href="route('shop.index')" variant="outline-light" size="sm" class="w-full">
                                Browse Supplement Shop
                            </x-ui.button>
                        </div>
                    </div>
                </article>

                {{-- Route 3: Physical Gym --}}
                <article class="group relative flex flex-col justify-between overflow-hidden rounded-sm border border-white/12 bg-charcoal">
                    <div class="aspect-[4/3] overflow-hidden bg-ink">
                        <img
                            src="/images/demo/route-gym-800.webp"
                            alt="Row of black dumbbells on a strength gym rack"
                            loading="lazy"
                            width="800"
                            height="1071"
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        />
                    </div>
                    <div class="flex flex-1 flex-col justify-between p-6 sm:p-7">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="eyebrow text-gold">03 · Physical Facility</span>
                                <x-ui.badge tone="dark">Memberships & Trials</x-ui.badge>
                            </div>
                            <h3 class="mt-2 font-display text-3xl text-ivory">Strength Gym & Floor Training</h3>
                            <p class="mt-2 text-sm leading-relaxed text-silver">
                                Calibrated free weights, squat racks, personal trainer assignments, attendance tracking, and structured class schedules.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-white/10">
                            <x-ui.button href="#gym-location" variant="outline-light" size="sm" class="w-full">
                                View Timings & Memberships
                            </x-ui.button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- SECTION 6: Authentic Transformation Results (Consent-Gated — No Fabricated Photos) --}}
    <section aria-labelledby="transformations-heading" class="section-y border-b border-white/10 bg-charcoal">
        <div class="container-site space-y-10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-ui.section-heading
                    eyebrow="Verified Outcomes & Privacy Policy"
                    title="Authentic Transformation Results"
                    subtitle="Client check-ins and progress photos are private by default. We never publish AI-generated physiques, stock photos, or unconsented client images."
                    surface="dark"
                />
                <x-ui.demo-flag label="Zero Fabricated Transformations Policy" />
            </div>

            @if ($transformations->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach ($transformations as $item)
                        <article class="rounded-sm border border-white/12 bg-ink p-6">
                            <h3 class="font-display text-2xl text-ivory">{{ $item->title }}</h3>
                            <p class="mt-2 text-sm text-silver">{{ $item->summary }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    @foreach ([
                        ['slot' => 'Verified Case Study Slot #01', 'focus' => '12-Week Fat Loss & Waist Reduction', 'metrics' => 'Weekly weigh-in log + waist circumference + strength retention log'],
                        ['slot' => 'Verified Case Study Slot #02', 'focus' => '16-Week Lean Hypertrophy Progression', 'metrics' => 'Barbell logbook progression + macronutrient adherence + consented imagery'],
                        ['slot' => 'Verified Case Study Slot #03', 'focus' => 'Busy Professional Body Recomposition', 'metrics' => '3x/week training schedule + South Asian household meal structure'],
                    ] as $slot)
                        <div class="rounded-sm border border-dashed border-gold/40 bg-ink/80 p-6 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="eyebrow text-gold">{{ $slot['slot'] }}</span>
                                    <x-ui.badge tone="demo">Awaiting Owner Consent File</x-ui.badge>
                                </div>
                                <h3 class="mt-3 font-display text-2xl text-ivory">{{ $slot['focus'] }}</h3>
                                <p class="mt-2 text-xs leading-relaxed text-silver">
                                    Database schema enforces a non-null <code class="text-gold">consent_record_id</code> foreign key before any transformation record can be published to the public site.
                                </p>
                            </div>
                            <p class="mt-5 border-t border-white/10 pt-3 text-[11px] text-silver">
                                Tracked indicators: {{ $slot['metrics'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- SECTION 7: Coaching Packages --}}
    <section id="coaching-packages" aria-labelledby="coaching-heading" class="section-y border-b border-white/10 bg-ink">
        <div class="container-site space-y-12">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-ui.section-heading
                    eyebrow="1-on-1 Online Coaching Tiers"
                    title="Structured Coaching Packages"
                    subtitle="Every coaching package includes an initial personalization intake, customized training and nutrition programming, and scheduled check-in accountability."
                    surface="dark"
                />
                <x-ui.button :href="route('coaching.index')" variant="outline-light" size="sm">
                    Compare All Coaching Features
                </x-ui.button>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($coachingPackages as $package)
                    <x-coaching.package-card :package="$package" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- SECTION 8: Featured Diet Programs --}}
    <section aria-labelledby="diet-heading" class="section-y border-b border-white/10 bg-charcoal">
        <div class="container-site space-y-12">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-ui.section-heading
                    eyebrow="Practical South Asian Nutrition"
                    title="Featured Diet Programs"
                    subtitle="Built around real Pakistani groceries—whole-wheat chapati, daal, chicken, beef, eggs, and rice—with gram and household measurements."
                    surface="dark"
                />
                <x-ui.button :href="route('diet-plans.show', 'pakistani-fat-loss-blueprint-demo')" variant="outline-light" size="sm">
                    View Sample Meal Schedule
                </x-ui.button>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($dietPlans as $plan)
                    <x-programs.diet-card :plan="$plan" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- SECTION 9: Best-Selling Supplements on Warm Off-White Background (#F7F5F0) --}}
    <section aria-labelledby="supplements-heading" class="section-y surface-paper border-b border-graphite/12">
        <div class="container-site space-y-12">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-ui.section-heading
                    eyebrow="Batch & Expiry Tracked Catalog · PKR"
                    title="Best-Selling Supplements & Gear"
                    subtitle="Warm editorial product presentation with transparent nutrition facts, allergen warnings, and verified stock availability."
                    surface="light"
                />
                <div class="flex items-center gap-3">
                    <x-ui.button :href="route('shop.index')" variant="graphite" size="md">
                        Shop All 12 Categories
                    </x-ui.button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($bestsellerProducts as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- SECTION 10: Coach Profile and Credentials --}}
    <section aria-labelledby="coach-heading" class="section-y border-b border-white/10 bg-ink">
        <div class="container-site">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-5">
                    <div class="relative overflow-hidden rounded-sm border border-white/15 bg-charcoal">
                        <img
                            src="/images/demo/route-coaching-800.webp"
                            alt="Coach training logbook and barbell preparation"
                            loading="lazy"
                            width="800"
                            height="1071"
                            class="aspect-[4/5] w-full object-cover"
                        />
                        <div class="border-t border-white/10 bg-charcoal p-4 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gold">Coach Assignment Architecture</span>
                            <x-ui.demo-flag label="Awaiting Owner Bio" />
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7 space-y-6">
                    <x-ui.section-heading
                        eyebrow="Head Coach & Qualifications"
                        :title="$headCoach?->job_title ?? 'Head Coach Profile [DEMO]'"
                        :subtitle="$headCoach?->headline ?? 'Reserved for authentic BIN MALIK FITNESS owner biography and verified coaching credentials.'"
                        surface="dark"
                    />

                    <p class="text-sm sm:text-base leading-relaxed text-silver">
                        {{ $headCoach?->bio }}
                    </p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-sm border border-white/10 bg-charcoal p-5">
                            <h3 class="eyebrow text-gold">Verified Qualification Slots</h3>
                            <ul class="mt-3 space-y-2 text-xs text-ivory">
                                @foreach ($headCoach?->qualifications ?? [] as $q)
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1 h-1.5 w-1.5 bg-gold shrink-0"></span>
                                        <span>{{ $q }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="rounded-sm border border-white/10 bg-charcoal p-5">
                            <h3 class="eyebrow text-gold">Coaching Specialties</h3>
                            <ul class="mt-3 space-y-2 text-xs text-ivory">
                                @foreach ($headCoach?->specialties ?? [] as $s)
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1 h-1.5 w-1.5 bg-gold shrink-0"></span>
                                        <span>{{ $s }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <p class="text-xs text-silver/80 border-l-2 border-gold pl-3">
                        Scope rule: Coaches act strictly within their verified qualifications. Any intake disclosing clinical or medical conditions is flagged for qualified professional review.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 11: Free Training Previews --}}
    <section id="free-previews" aria-labelledby="videos-heading" class="section-y border-b border-white/10 bg-charcoal">
        <div class="container-site space-y-12">
            <x-ui.section-heading
                eyebrow="Execution Standards & Coaching Systems"
                title="Free Training Video Previews"
                subtitle="Sample public breakdowns from our exercise and check-in library. Full instructional collections are unlocked server-side for active coaching subscribers."
                surface="dark"
            />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($freeVideos as $video)
                    <article class="flex flex-col justify-between rounded-sm border border-white/12 bg-ink overflow-hidden">
                        <div class="relative aspect-video bg-charcoal">
                            <img
                                src="{{ $video->thumbnail_path }}"
                                alt="{{ $video->title }}"
                                loading="lazy"
                                width="768"
                                height="429"
                                class="h-full w-full object-cover opacity-80"
                            />
                            <div class="absolute inset-0 flex items-center justify-center bg-ink/40">
                                <span class="inline-flex items-center gap-2 rounded-xs bg-gold px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-ink">
                                    ▶ Free Preview · {{ $video->formattedDuration() }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold uppercase tracking-wider text-gold">{{ $video->category }}</span>
                                <x-ui.demo-flag label="Sample Video" />
                            </div>
                            <h3 class="font-display text-2xl text-ivory">{{ $video->title }}</h3>
                            <p class="text-xs leading-relaxed text-silver">{{ $video->summary }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- SECTION 12: Verified Testimonials --}}
    <section aria-labelledby="testimonials-heading" class="section-y border-b border-white/10 bg-ink">
        <div class="container-site space-y-10">
            <x-ui.section-heading
                eyebrow="Zero Fabricated Reviews"
                title="Verified Client Testimonials"
                subtitle="Every testimonial and product review must link to a verified order/entitlement and a documented consent record."
                surface="dark"
            />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ([
                    ['line' => 'Online Coaching', 'rule' => 'Requires active/completed coaching_subscription + consent_record_id'],
                    ['line' => 'Supplement Shop', 'rule' => 'Requires delivered order_item (verified purchase badge) + moderation'],
                    ['line' => 'Physical Gym', 'rule' => 'Requires verified gym_membership + member consent sign-off'],
                ] as $slot)
                    <div class="rounded-sm border border-white/12 bg-charcoal p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="eyebrow text-gold">{{ $slot['line'] }}</span>
                            <x-ui.badge tone="demo">Verification Gate Active</x-ui.badge>
                        </div>
                        <p class="text-sm text-ivory font-medium">
                            Reserved for authentic {{ strtolower($slot['line']) }} feedback provided by the owner or submitted by verified customers.
                        </p>
                        <p class="text-xs text-silver">
                            Database integrity rule: <code class="text-gold">{{ $slot['rule'] }}</code>. No synthetic star ratings are emitted in JSON-LD schema.
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- SECTION 13: FAQ --}}
    <section id="faq" aria-labelledby="faq-heading" class="section-y border-b border-white/10 bg-charcoal">
        <div class="container-site">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <x-ui.section-heading
                        eyebrow="Clarity Before Commitment"
                        title="Frequently Asked Questions"
                        subtitle="How online coaching onboarding, supplement authenticity verification, and manual bank transfer confirmations operate."
                        surface="dark"
                    />
                </div>

                <div class="lg:col-span-7 space-y-3" x-data="{ activeFaq: 0 }">
                    @foreach ($faqs as $idx => $faq)
                        <div class="rounded-sm border border-white/12 bg-ink">
                            <h3>
                                <button
                                    type="button"
                                    @click="activeFaq = (activeFaq === {{ $idx }} ? null : {{ $idx }})"
                                    :aria-expanded="(activeFaq === {{ $idx }}).toString()"
                                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-sm font-semibold text-ivory hover:text-gold"
                                >
                                    <span>{{ $faq->question }}</span>
                                    <span class="font-mono text-gold" x-text="activeFaq === {{ $idx }} ? '−' : '+'">+</span>
                                </button>
                            </h3>
                            <div x-show="activeFaq === {{ $idx }}" x-collapse class="border-t border-white/10 px-5 py-4 text-sm leading-relaxed text-silver">
                                {{ $faq->answer }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 14: Gym Location and Operating Hours --}}
    <section id="gym-location" aria-labelledby="gym-heading" class="section-y bg-ink">
        <div class="container-site space-y-12">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-ui.section-heading
                    eyebrow="Physical Training Facility · Configurable Location"
                    title="Gym Location, Memberships & Operating Hours"
                    subtitle="Physical address and city remain configurable in Platform Settings until confirmed by the owner."
                    surface="dark"
                />
                <x-ui.demo-flag label="Location Configurable" />
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <div class="lg:col-span-5 space-y-6">
                    <div class="rounded-sm border border-white/12 bg-charcoal p-6 space-y-4">
                        <h3 class="font-display text-2xl text-ivory">Facility Details</h3>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-gold">Confirmed City</dt>
                                <dd class="mt-0.5 text-ivory">{{ $gymCity }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-gold">Street Address</dt>
                                <dd class="mt-0.5 text-silver">{{ $gymAddress }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-gold">Front Desk & Trial Enquiries</dt>
                                <dd class="mt-0.5 text-ivory">{{ $gymPhone }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-4">
                        @foreach ($membershipPlans as $mPlan)
                            <div class="rounded-sm border {{ $mPlan->is_featured ? 'border-gold' : 'border-white/12' }} bg-charcoal p-5 flex items-center justify-between gap-4">
                                <div>
                                    <span class="eyebrow text-gold">{{ $mPlan->duration_days }} Days Access</span>
                                    <h4 class="mt-1 font-display text-2xl text-ivory">{{ $mPlan->name }}</h4>
                                    <p class="text-xs text-silver">{{ implode(' · ', $mPlan->inclusions ?? []) }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <x-ui.price :paisa="$mPlan->price" surface="dark" size="md" />
                                    <span class="block text-[11px] text-silver">Counter / Bank Verified</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="lg:col-span-7">
                    <div class="rounded-sm border border-white/12 bg-charcoal p-6">
                        <div class="flex items-center justify-between gap-2 pb-4 border-b border-white/10">
                            <h3 class="font-display text-2xl text-ivory">Weekly Operating Hours (PKT)</h3>
                            <x-ui.demo-flag label="Sample Schedule" />
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full text-left text-xs sm:text-sm">
                                <thead>
                                    <tr class="border-b border-white/10 text-gold uppercase text-[11px] tracking-wider">
                                        <th class="py-2.5 pr-3">Day</th>
                                        <th class="py-2.5 px-3">General Hours</th>
                                        <th class="py-2.5 px-3">Dedicated Window</th>
                                        <th class="py-2.5 pl-3">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/10">
                                    @foreach ($openingHours as $hour)
                                        <tr>
                                            <td class="py-3 pr-3 font-semibold text-ivory">{{ $hour->day_name }}</td>
                                            <td class="py-3 px-3 tabular-nums text-ivory">
                                                {{ $hour->is_closed ? 'Closed' : substr((string) $hour->opens_at, 0, 5).' – '.substr((string) $hour->closes_at, 0, 5) }}
                                            </td>
                                            <td class="py-3 px-3 text-silver">{{ $hour->ladies_only_window ?? 'Standard Floor' }}</td>
                                            <td class="py-3 pl-3 text-xs text-silver">{{ $hour->note }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
