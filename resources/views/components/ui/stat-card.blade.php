@props([
    'label',
    'value',
    'meta' => null,
    'surface' => 'light', // light | dark
])

@php
    $box = $surface === 'dark'
        ? 'bg-charcoal border-white/10 text-ivory'
        : 'bg-white border-graphite/10 text-graphite';
    $muted = $surface === 'dark' ? 'text-silver' : 'text-graphite/65';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-sm border p-5 '.$box]) }}>
    <p class="text-xs font-semibold uppercase tracking-wider {{ $muted }}">{{ $label }}</p>
    <p class="mt-2 font-display text-3xl sm:text-4xl tracking-wide tabular-nums">{{ $value }}</p>
    @if ($meta)
        <p class="mt-1.5 text-xs {{ $muted }}">{{ $meta }}</p>
    @endif
</div>
