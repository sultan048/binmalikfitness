@php
    $seoService = app(\App\Domain\Platform\SeoAndStructuredDataService::class);
    $seoMeta = $seoService->resolveMetaForModel(
        model: $package,
        defaultTitle: $package->name.' — BIN MALIK FITNESS Coaching',
        defaultDescription: (string) ($package->tagline ?? $package->name),
        defaultCanonical: route('coaching.show', $package->slug),
    );
    $coachingSchemas = [
        $seoService->coachingServiceSchema($package),
        $seoService->breadcrumbSchema([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Online Coaching', 'url' => route('coaching.index')],
            ['name' => $package->name, 'url' => route('coaching.show', $package->slug)],
        ]),
    ];
@endphp

<x-layouts.public
    :title="$seoMeta['title']"
    :description="$seoMeta['description']"
    :canonical="$seoMeta['canonical']"
    :og-image="$seoMeta['og_image']"
    :robots="$seoMeta['robots']"
    :json-ld-schemas="$coachingSchemas"
>
    <section class="section-y border-b border-white/10 bg-ink">
        <div class="container-site space-y-10">
            @if (session('status'))
                <div class="rounded-sm border border-gold/40 bg-gold/10 px-4 py-3 text-sm font-medium text-ivory">
                    {{ session('status') }}
                </div>
            @endif

            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-silver">
                <a href="{{ route('home') }}" class="hover:text-gold">Home</a>
                <span>/</span>
                <a href="{{ route('coaching.index') }}" class="hover:text-gold">Online Coaching</a>
                <span>/</span>
                <span class="text-ivory font-semibold">{{ $package->name }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <div class="lg:col-span-7 space-y-8">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="eyebrow text-gold">{{ intdiv($package->duration_days, 7) }} Weeks ({{ $package->duration_days }} Days) · {{ $package->checkins_per_week }}x Weekly Check-In</span>
                            <x-ui.demo-flag label="Stage 4 Enrolment Active" />
                        </div>
                        <h1 class="display-l text-ivory">{{ $package->name }}</h1>
                        <p class="text-base sm:text-lg text-silver">{{ $package->tagline }}</p>
                    </div>

                    <div class="rounded-sm border border-white/12 bg-charcoal p-6 space-y-4">
                        <h2 class="font-display text-2xl text-ivory">What Is Included in Your Entitlement</h2>
                        <ul class="space-y-3 text-sm text-ivory/90">
                            @foreach ($package->inclusions ?? [] as $inc)
                                <li class="flex items-start gap-3">
                                    <span class="mt-1.5 h-2 w-2 bg-gold shrink-0"></span>
                                    <span>{{ $inc }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- 12-Point Intake Preview --}}
                    <div class="rounded-sm border border-white/12 bg-charcoal p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-display text-2xl text-ivory">Personalization Intake Fields (Completed in Portal)</h2>
                            <x-ui.badge tone="gold">12 Required Parameters</x-ui.badge>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-3 text-silver">
                            <div class="rounded-xs bg-ink p-2.5">1. Primary Fitness Goal</div>
                            <div class="rounded-xs bg-ink p-2.5">2. Age (Years)</div>
                            <div class="rounded-xs bg-ink p-2.5">3. Height (cm)</div>
                            <div class="rounded-xs bg-ink p-2.5">4. Current Weight (kg)</div>
                            <div class="rounded-xs bg-ink p-2.5">5. Daily Activity Level</div>
                            <div class="rounded-xs bg-ink p-2.5">6. Training Experience</div>
                            <div class="rounded-xs bg-ink p-2.5">7. Local Food Preferences</div>
                            <div class="rounded-xs bg-ink p-2.5">8. Food Allergies</div>
                            <div class="rounded-xs bg-ink p-2.5">9. Dietary Restrictions</div>
                            <div class="rounded-xs bg-ink p-2.5">10. Equipment Availability</div>
                            <div class="rounded-xs bg-ink p-2.5">11. Weekly Schedule</div>
                            <div class="rounded-xs bg-ink p-2.5">12. Consented Health Notes</div>
                        </div>
                        <p class="text-xs text-silver/85 border-l-2 border-gold pl-3">
                            Medical Safeguard: We do not automatically prescribe clinical diets or claim to treat medical conditions. Disclosures requiring medical clearance are automatically flagged for qualified professional review.
                        </p>
                    </div>
                </div>

                {{-- Enrolment Card --}}
                <aside class="lg:col-span-5">
                    <div class="sticky top-24 rounded-sm border border-gold bg-charcoal p-6 sm:p-7 space-y-6">
                        <div class="flex items-center justify-between">
                            <span class="eyebrow text-gold">Enrolment Summary</span>
                            <x-ui.badge tone="gold">Manual Renewal Only</x-ui.badge>
                        </div>

                        <div>
                            <x-ui.price :paisa="$package->price" :compare-at-paisa="$package->compare_at_price" surface="dark" size="lg" />
                            <p class="mt-1 text-xs text-silver">{{ $package->billing_note }}</p>
                        </div>

                        <dl class="divide-y divide-white/10 border-y border-white/10 text-xs">
                            <div class="flex justify-between py-2.5">
                                <dt class="text-silver">Program Duration</dt>
                                <dd class="font-semibold text-ivory">{{ $package->duration_days }} Days</dd>
                            </div>
                            <div class="flex justify-between py-2.5">
                                <dt class="text-silver">Check-In Cadence</dt>
                                <dd class="font-semibold text-ivory">{{ $package->checkins_per_week }}x per week</dd>
                            </div>
                            <div class="flex justify-between py-2.5">
                                <dt class="text-silver">Premium Video Access</dt>
                                <dd class="font-semibold text-gold">Server-Side Entitled</dd>
                            </div>
                            <div class="flex justify-between py-2.5">
                                <dt class="text-silver">Pause Allowance</dt>
                                <dd class="font-semibold text-ivory">Up to 14 Days / Term</dd>
                            </div>
                            <div class="flex justify-between py-2.5">
                                <dt class="text-silver">Outcome Privacy</dt>
                                <dd class="font-semibold text-ivory">Private by Default</dd>
                            </div>
                        </dl>

                        @if ($activeSubscription)
                            <div class="rounded-xs border border-gold/40 bg-ink p-4 space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold uppercase tracking-wider text-gold">Your Subscription ({{ $activeSubscription->subscription_number }})</span>
                                    <span class="rounded-xs bg-gold/20 px-2 py-0.5 font-bold uppercase text-gold">{{ $activeSubscription->status }}</span>
                                </div>
                                @if ($activeSubscription->ends_at)
                                    <p class="text-silver">Valid until {{ $activeSubscription->ends_at->format('d M Y') }} ({{ $activeSubscription->daysRemaining() }} days remaining)</p>
                                @else
                                    <p class="text-silver">Awaiting staff payment verification before activation.</p>
                                @endif
                                <a href="{{ route('account.dashboard') }}#coaching-hub" class="inline-block font-bold text-gold underline">
                                    Open Coaching Hub in Customer Portal →
                                </a>
                            </div>
                        @endif

                        @auth
                            <form method="POST" action="{{ route('coaching.enrol', $package->slug) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="field-label text-ivory">Select Payment Method (PKR)</label>
                                    <select name="payment_method" class="field-input-dark">
                                        <option value="bank_transfer">Manual Bank Transfer / IBFT (With Staff Verification)</option>
                                        <option value="cod">In-Person / Counter Cash Confirmation</option>
                                    </select>
                                </div>
                                <button type="submit" class="w-full rounded-xs bg-gold px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-ink transition hover:bg-gold/90">
                                    Enrol & Create Coaching Order ({{ \App\Support\Money::format($package->price) }})
                                </button>
                            </form>
                        @else
                            <div class="space-y-3">
                                <x-ui.button :href="route('login')" variant="gold" size="lg" class="w-full">
                                    Sign In / Register to Enrol in Coaching
                                </x-ui.button>
                            </div>
                        @endauth

                        <x-ui.button :href="route('coaching.index')" variant="outline-light" size="md" class="w-full">
                            Compare Other Durations
                        </x-ui.button>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.public>
