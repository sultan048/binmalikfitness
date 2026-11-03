@php
    $seoService = app(\App\Domain\Platform\SeoAndStructuredDataService::class);
    $seoMeta = $seoService->resolveMetaForModel(
        model: $plan,
        defaultTitle: $plan->title.' — BIN MALIK FITNESS Diet Plans',
        defaultDescription: (string) ($plan->summary ?? $plan->title),
        defaultCanonical: route('diet-plans.show', $plan->slug),
    );
    $dietSchemas = [
        $seoService->dietPlanSchema($plan),
        $seoService->breadcrumbSchema([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Diet Plans', 'url' => route('coaching.index').'#diet-plans'],
            ['name' => $plan->title, 'url' => route('diet-plans.show', $plan->slug)],
        ]),
    ];
@endphp

<x-layouts.public
    :title="$seoMeta['title']"
    :description="$seoMeta['description']"
    :canonical="$seoMeta['canonical']"
    :og-image="$seoMeta['og_image']"
    :robots="$seoMeta['robots']"
    :json-ld-schemas="$dietSchemas"
>
    <section class="section-y bg-ink border-b border-white/10">
        <div class="container-site space-y-10">
            @if (session('status'))
                <div class="rounded-sm border border-gold/40 bg-gold/10 px-4 py-3 text-sm font-medium text-ivory">
                    {{ session('status') }}
                </div>
            @endif

            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-silver">
                <a href="{{ route('home') }}" class="hover:text-gold">Home</a>
                <span>/</span>
                <a href="{{ route('coaching.index') }}#diet-plans" class="hover:text-gold">Diet Plans</a>
                <span>/</span>
                <span class="text-ivory font-semibold">{{ $plan->title }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <div class="lg:col-span-7 space-y-8">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="eyebrow text-gold">{{ $plan->category }} · {{ strtoupper($plan->type) }}</span>
                            @if ($isEntitled)
                                <x-ui.badge tone="gold">Entitled · Full Plan Unlocked</x-ui.badge>
                            @else
                                <x-ui.badge tone="dark">Free Preview Mode</x-ui.badge>
                            @endif
                        </div>
                        <h1 class="display-l text-ivory">{{ $plan->title }}</h1>
                        <p class="text-base text-silver leading-relaxed">{{ $plan->summary }}</p>
                    </div>

                    @if ($isEntitled && $activeVersion)
                        {{-- FULL ENTITLED DIET PLAN VERSION VIEW --}}
                        <div class="rounded-sm border border-gold/50 bg-charcoal p-6 space-y-6">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
                                <div>
                                    <span class="eyebrow text-gold">Entitled Nutrition Blueprint · Version v{{ $activeVersion->version_number }}</span>
                                    <h2 class="font-display text-2xl text-ivory">{{ $activeVersion->title }}</h2>
                                    @if ($activeVersion->revision_note)
                                        <p class="text-xs text-silver">{{ $activeVersion->revision_note }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-xs bg-ink px-3 py-1.5 font-mono text-gold">{{ $activeVersion->daily_calories }} kcal</span>
                                    <span class="rounded-xs bg-ink px-3 py-1.5 font-mono text-ivory">{{ $activeVersion->protein_grams }}g P</span>
                                    <span class="rounded-xs bg-ink px-3 py-1.5 font-mono text-ivory">{{ $activeVersion->carbs_grams }}g C</span>
                                    <span class="rounded-xs bg-ink px-3 py-1.5 font-mono text-ivory">{{ $activeVersion->fat_grams }}g F</span>
                                    <span class="rounded-xs bg-ink px-3 py-1.5 font-mono text-silver">{{ $activeVersion->hydration_target_liters }}L Water</span>
                                </div>
                            </div>

                            {{-- Structured Meal Schedule --}}
                            <div class="space-y-3">
                                <h3 class="font-display text-xl text-ivory">Structured Daily Meal Schedule (Gram & Household Measures)</h3>
                                @foreach ($activeVersion->meal_schedule ?? [] as $row)
                                    <div class="rounded-xs border border-white/10 bg-ink p-4 space-y-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-gold">{{ $row['meal'] ?? 'Meal' }}</h4>
                                            <span class="text-[11px] font-mono text-silver">{{ $row['macros'] ?? '' }}</span>
                                        </div>
                                        <p class="text-sm text-ivory">{{ $row['items'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Approved Food Substitutions --}}
                            @if (! empty($activeVersion->substitutions))
                                <div class="space-y-3 border-t border-white/10 pt-5">
                                    <h3 class="font-display text-xl text-ivory">Approved Iso-Caloric Food Substitution Table</h3>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        @foreach ($activeVersion->substitutions as $sub)
                                            <div class="rounded-xs border border-white/10 bg-ink p-4 space-y-1.5 text-xs">
                                                <span class="font-bold uppercase text-gold block">{{ $sub['group'] ?? 'Food Group' }}</span>
                                                <p class="text-ivory"><strong>Baseline:</strong> {{ $sub['original_item'] ?? '' }}</p>
                                                <p class="text-silver"><strong>Approved Swaps:</strong> {{ implode(' · ', $sub['swap_options'] ?? []) }}</p>
                                                @if (! empty($sub['notes']))
                                                    <p class="text-[11px] text-silver/75">{{ $sub['notes'] }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Weekly Shopping List --}}
                            @if (! empty($activeVersion->shopping_list))
                                <div class="space-y-3 border-t border-white/10 pt-5">
                                    <h3 class="font-display text-xl text-ivory">Weekly Mandi &amp; Supermarket Shopping List</h3>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs">
                                        @foreach ($activeVersion->shopping_list as $groupName => $items)
                                            <div class="rounded-xs bg-ink p-4 space-y-2">
                                                <span class="font-bold uppercase text-gold block">{{ $groupName }}</span>
                                                <ul class="space-y-1 text-silver">
                                                    @foreach ((array) $items as $gItem)
                                                        <li>• {{ $gItem }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($userPlanRequest)
                                {{-- Interactive Customer Substitution Selection & Revision Request Form --}}
                                <form
                                    method="POST"
                                    action="{{ route('account.diet-requests.preferences.update', $userPlanRequest) }}"
                                    class="space-y-4 border-t border-white/10 pt-5"
                                >
                                    @csrf
                                    @method('PUT')
                                    <h3 class="font-display text-xl text-ivory">Customise Approved Swaps or Request a Coach Revision</h3>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        @foreach ($activeVersion->substitutions ?? [] as $sub)
                                            @php
                                                $groupKey = $sub['group'] ?? 'Group';
                                                $currentChoice = $userPlanRequest->custom_substitution_overrides[$groupKey] ?? '';
                                            @endphp
                                            <div>
                                                <label class="field-label text-xs text-ivory">{{ $groupKey }}</label>
                                                <select name="substitutions[{{ $groupKey }}]" class="field-input-dark text-xs">
                                                    <option value="{{ $sub['original_item'] ?? '' }}">Default: {{ $sub['original_item'] ?? '' }}</option>
                                                    @foreach ($sub['swap_options'] ?? [] as $opt)
                                                        <option value="{{ $opt }}" @selected($currentChoice === $opt)>Swap: {{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div>
                                        <label class="field-label text-xs text-ivory">Optional Revision Request Notes for Assigned Coach</label>
                                        <textarea
                                            name="revision_notes"
                                            rows="2"
                                            placeholder="e.g. Shifting training to morning Ramadan hours or swapping dinner protein..."
                                            class="field-input-dark text-xs"
                                        >{{ $userPlanRequest->customer_revision_notes }}</textarea>
                                    </div>
                                    <button type="submit" class="rounded-xs bg-gold px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-ink">
                                        Save Swap Preferences / Request Plan Revision
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        {{-- Free Preview Sample Day --}}
                        <div class="rounded-sm border border-white/12 bg-charcoal p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <h2 class="font-display text-2xl text-ivory">Free Preview: Sample Day Meal Schedule</h2>
                                <x-ui.badge tone="gold">Pakistani Kitchen Staples</x-ui.badge>
                            </div>

                            @if (! empty($plan->sample_day))
                                <div class="space-y-3">
                                    @foreach ($plan->sample_day as $row)
                                        <div class="rounded-xs border border-white/10 bg-ink p-4 space-y-1">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <h3 class="text-xs font-bold uppercase tracking-wider text-gold">{{ $row['meal'] }}</h3>
                                                <span class="text-[11px] font-mono text-silver">{{ $row['macros'] }}</span>
                                            </div>
                                            <p class="text-sm text-ivory">{{ $row['items'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-silver">
                                    This is a personalised plan prepared by an assigned staff member after reviewing your 12-point intake.
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Approved Substitution Preview --}}
                    <div class="rounded-sm border border-white/12 bg-charcoal p-6 space-y-3">
                        <h2 class="font-display text-2xl text-ivory">Approved Food Substitution Matrix (Sample)</h2>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-xs">
                            <div class="rounded-xs bg-ink p-3.5">
                                <span class="font-bold text-gold uppercase block">Lean Protein Equivalents</span>
                                <span class="text-silver mt-1 block">150g Chicken Breast ↔ 160g White Fish ↔ 5 Egg Whites + 1 Whole Egg</span>
                            </div>
                            <div class="rounded-xs bg-ink p-3.5">
                                <span class="font-bold text-gold uppercase block">Carbohydrate Equivalents</span>
                                <span class="text-silver mt-1 block">45g Whole-Wheat Atta Chapati ↔ 150g Cooked Basmati Rice ↔ 200g Boiled Potato</span>
                            </div>
                            <div class="rounded-xs bg-ink p-3.5">
                                <span class="font-bold text-gold uppercase block">Healthy Fats</span>
                                <span class="text-silver mt-1 block">1 tsp Desi Ghee / Olive Oil ↔ 15g Almonds ↔ 1 tbsp Peanut Butter</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-silver/80 border-l-2 border-gold pl-3">
                        {{ $plan->disclaimer }}
                    </p>
                </div>

                {{-- Purchase / Entitlement Box --}}
                <aside class="lg:col-span-5">
                    <div class="sticky top-24 overflow-hidden rounded-sm border border-white/15 bg-charcoal">
                        @if ($plan->image_path)
                            <img src="{{ $plan->image_path }}" alt="{{ $plan->title }}" class="aspect-[3/2] w-full object-cover" />
                        @endif
                        <div class="p-6 space-y-5">
                            <div class="flex items-center justify-between">
                                @if ($plan->price > 0)
                                    <x-ui.price :paisa="$plan->price" surface="dark" size="lg" />
                                @else
                                    <span class="font-display text-3xl text-gold">FREE GUIDE</span>
                                @endif
                                <x-ui.badge tone="dark">{{ $plan->duration_days }} Days Plan</x-ui.badge>
                            </div>

                            <ul class="space-y-2 text-xs text-ivory/90">
                                @foreach ($plan->inclusions ?? [] as $inc)
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1 h-1.5 w-1.5 bg-gold shrink-0"></span>
                                        <span>{{ $inc }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($isEntitled)
                                <div class="rounded-xs border border-gold/40 bg-ink p-4 text-xs text-ivory space-y-2">
                                    <p class="font-bold text-gold uppercase">Active Entitlement Unlocked</p>
                                    <p class="text-silver">You have full server-side access to the meal schedule, approved substitution matrix, and weekly shopping list on this page.</p>
                                    @if ($userVersions->count() > 0)
                                        <p class="text-[11px] text-silver">Available revisions: {{ $userVersions->pluck('version_number')->map(fn($v) => 'v'.$v)->implode(', ') }}</p>
                                    @endif
                                </div>
                            @else
                                @auth
                                    <form method="POST" action="{{ route('diet-plans.purchase', $plan->slug) }}" class="space-y-3">
                                        @csrf
                                        <div>
                                            <label class="field-label text-xs text-ivory">Payment Method (PKR)</label>
                                            <select name="payment_method" class="field-input-dark text-xs">
                                                <option value="bank_transfer">Manual Bank Transfer / IBFT (With Staff Verification)</option>
                                                <option value="cod">In-Person / Counter Cash Confirmation</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="w-full rounded-xs bg-gold px-4 py-3 text-xs font-bold uppercase tracking-wider text-ink transition hover:bg-gold/90">
                                            {{ $plan->type === 'personalised' ? 'Purchase & Start Personalisation Intake' : 'Purchase Diet Plan Entitlement' }}
                                        </button>
                                    </form>
                                @else
                                    <x-ui.button :href="route('login')" variant="gold" size="lg" class="w-full">
                                        Sign In to Unlock Diet Plan
                                    </x-ui.button>
                                @endauth
                            @endif

                            <p class="text-[11px] text-silver text-center">
                                Access is controlled by server-side <code class="text-gold">entitlements</code> after payment verification.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.public>
