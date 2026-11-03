@props(['plan'])

<article class="flex flex-col justify-between rounded-sm border border-white/12 bg-charcoal p-6 transition hover:border-gold/50">
    <div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="eyebrow text-gold">{{ $plan->category }}</span>
            <div class="flex items-center gap-1.5">
                @if ($plan->is_demo)
                    <x-ui.demo-flag label="Demo Plan" />
                @endif
                <x-ui.badge tone="dark">{{ $plan->type === 'personalised' ? 'Staff Prepared' : 'Instant Entitlement' }}</x-ui.badge>
            </div>
        </div>

        <h3 class="mt-2.5 font-display text-2xl sm:text-3xl tracking-wide text-ivory">
            <a href="{{ route('diet-plans.show', $plan->slug) }}" class="hover:text-gold">
                {{ $plan->title }}
            </a>
        </h3>

        <p class="mt-2 text-sm leading-relaxed text-silver">{{ $plan->summary }}</p>

        <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-white/10 pt-3 text-xs">
            <div>
                <dt class="text-silver">Duration</dt>
                <dd class="mt-0.5 font-semibold text-ivory">{{ $plan->duration_days }} Days</dd>
            </div>
            <div>
                <dt class="text-silver">Calorie Structure</dt>
                <dd class="mt-0.5 font-semibold text-ivory">{{ $plan->target_calories ?? 'Custom' }}</dd>
            </div>
        </dl>

        @if (! empty($plan->dietary_tags))
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach ($plan->dietary_tags as $tag)
                    <span class="rounded-xs bg-white/5 px-2 py-0.5 text-[11px] text-silver">{{ $tag }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-6 flex items-center justify-between gap-4 border-t border-white/10 pt-4">
        <x-ui.price :paisa="$plan->price" surface="dark" size="md" />
        <x-ui.button :href="route('diet-plans.show', $plan->slug)" variant="outline-light" size="sm">
            Preview & Details
        </x-ui.button>
    </div>
</article>
