@props([
    'title',
    'description',
    'actionLabel' => null,
    'actionHref' => null,
    'surface' => 'light',
])

@php
    $box = $surface === 'dark'
        ? 'border-white/15 bg-charcoal/70 text-ivory'
        : 'border-graphite/15 bg-paper text-graphite';
    $muted = $surface === 'dark' ? 'text-silver' : 'text-graphite/70';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-sm border border-dashed p-6 sm:p-8 text-center '.$box]) }}>
    <h3 class="font-display text-2xl uppercase tracking-wide">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed {{ $muted }}">{{ $description }}</p>
    @if ($actionLabel && $actionHref)
        <div class="mt-5">
            <x-ui.button :href="$actionHref" :variant="$surface === 'dark' ? 'gold' : 'graphite'" size="sm">
                {{ $actionLabel }}
            </x-ui.button>
        </div>
    @endif
</div>
