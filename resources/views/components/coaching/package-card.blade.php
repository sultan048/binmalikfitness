@props(['package'])

@php
    $featured = (bool) $package->is_featured;
    $cardClass = $featured
        ? 'border-gold bg-charcoal ring-1 ring-gold/40'
        : 'border-white/12 bg-charcoal/80 hover:border-white/25';
@endphp

<article class="relative flex flex-col justify-between rounded-sm border p-6 sm:p-7 transition {{ $cardClass }}">
    @if ($featured)
        <div class=" -mt-9 mb-4 self-start">
            <x-ui.badge tone="gold">Primary Recommended Tier</x-ui.badge>
        </div>
    @endif

    <div>
        <div class="flex items-center justify-between gap-2">
            <span class="eyebrow text-gold">{{ intdiv($package->duration_days, 7) }} Weeks · {{ $package->checkins_per_week }}x Weekly Check-In</span>
            @if ($package->is_demo)
                <x-ui.demo-flag label="Sample Tier" />
            @endif
        </div>

        <h3 class="mt-2 font-display text-3xl tracking-wide text-ivory">
            <a href="{{ route('coaching.show', $package->slug) }}" class="hover:text-gold">
                {{ $package->name }}
            </a>
        </h3>

        @if ($package->tagline)
            <p class="mt-2 text-sm leading-relaxed text-silver">{{ $package->tagline }}</p>
        @endif

        <div class="mt-5 border-y border-white/10 py-4">
            <x-ui.price :paisa="$package->price" :compare-at-paisa="$package->compare_at_price" surface="dark" size="lg" />
            @if ($package->billing_note)
                <p class="mt-1 text-xs text-silver">{{ $package->billing_note }}</p>
            @endif
        </div>

        <ul class="mt-5 space-y-2.5 text-sm text-ivory/90">
            @foreach ($package->inclusions ?? [] as $line)
                <li class="flex items-start gap-2.5">
                    <span class="mt-1 inline-block h-2 w-2 shrink-0 bg-gold" aria-hidden="true"></span>
                    <span>{{ $line }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-7 space-y-2.5">
        <x-ui.button :href="route('coaching.show', $package->slug)" :variant="$featured ? 'gold' : 'outline-light'" class="w-full">
            Start Coaching Application
        </x-ui.button>
        <p class="text-center text-[11px] text-silver">
            Manual verification · No auto-recurring card charges
        </p>
    </div>
</article>
