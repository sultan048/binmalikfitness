{{-- Tab panel "coaching" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<div id="coaching-diet-admin" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="coaching" x-show="tab === 'coaching'" x-cloak>
    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-graphite">Coaching Packages & Subscriptions ({{ $subscriptions->count() }})</h2>
            <x-ui.badge tone="gold">coaching.packages.manage</x-ui.badge>
        </div>

        <form method="POST" action="{{ route('manage.coaching-packages.store') }}" class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 border-b border-graphite/10 pb-4 text-xs">
            @csrf
            <input type="text" name="name" placeholder="20-Week Prep Tier" class="field-input text-xs sm:col-span-2" required />
            <input type="number" name="duration_days" value="140" min="7" class="field-input text-xs" placeholder="Days" required />
            <input type="number" name="price_pkr" value="64000" min="1000" class="field-input text-xs" placeholder="PKR" required />
            <input type="text" name="tagline" value="High-touch strength & nutrition coaching tier" class="field-input text-xs sm:col-span-2" required />
            <input type="number" name="checkins_per_week" value="2" min="1" max="7" class="field-input text-xs" required />
            <x-ui.button type="submit" variant="graphite" size="sm">Add Package</x-ui.button>
        </form>

        <div class="space-y-2 text-xs">
            @foreach ($subscriptions as $sub)
                <div class="flex items-center justify-between rounded-xs bg-paper px-3 py-2">
                    <div>
                        <strong class="font-mono text-graphite">{{ $sub->subscription_number }}</strong> — {{ $sub->user?->name }}
                        <span class="block text-[11px] text-graphite/70">{{ $sub->package?->name }} · Coach: {{ $sub->coach?->name ?? 'Unassigned' }}</span>
                    </div>
                    <span class="rounded-xs bg-charcoal px-2 py-0.5 text-[10px] font-bold uppercase text-gold">{{ $sub->status }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="lg:col-span-6 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-graphite">Master Diet Plans ({{ $dietPlans->count() }}) & Intakes ({{ $intakes->count() }})</h2>
            <x-ui.badge tone="gold">diet.plans.manage</x-ui.badge>
        </div>

        <form method="POST" action="{{ route('manage.diet-plans.store') }}" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 border-b border-graphite/10 pb-4 text-xs">
            @csrf
            <input type="text" name="title" placeholder="Ramadan Strength & Fat-Loss Plan" class="field-input text-xs sm:col-span-2" required />
            <select name="category" class="field-input text-xs" required>
                @foreach (\App\Models\DietPlan::CATEGORIES as $dCat)
                    <option value="{{ $dCat }}">{{ $dCat }}</option>
                @endforeach
            </select>
            <select name="type" class="field-input text-xs">
                <option value="paid">Paid Digital Plan</option>
                <option value="personalised">Personalised (Intake)</option>
                <option value="free">Free Starter Plan</option>
            </select>
            <input type="number" name="duration_days" value="30" class="field-input text-xs" placeholder="Days" required />
            <input type="number" name="price_pkr" value="4800" class="field-input text-xs" placeholder="PKR" required />
            <input type="text" name="summary" value="Structured Suhoor & Iftar high-protein meal schedule" class="field-input text-xs sm:col-span-2" required />
            <x-ui.button type="submit" variant="graphite" size="sm">Create Diet Plan</x-ui.button>
        </form>

        <div class="space-y-2 text-xs">
            @foreach ($intakes->take(4) as $in)
                <div class="flex items-center justify-between rounded-xs {{ $in->needs_professional_review ? 'border border-danger/40 bg-danger/5' : 'bg-paper' }} px-3 py-2">
                    <div>
                        <strong>Intake #{{ $in->id }} ({{ $in->user?->name }})</strong> — {{ $in->fitness_goal }}
                        @if ($in->review_flag_reason)
                            <span class="block text-[11px] text-danger">{{ $in->review_flag_reason }}</span>
                        @endif
                    </div>
                    <span class="font-mono text-[10px] font-bold uppercase text-gold-dark">{{ $in->review_status }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
