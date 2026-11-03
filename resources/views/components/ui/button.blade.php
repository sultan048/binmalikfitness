@props([
    'variant' => 'gold', // gold | graphite | outline-light | outline-dark | ghost
    'size' => 'md', // sm | md | lg
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-sans font-semibold uppercase tracking-wider transition duration-150 rounded-xs select-none cursor-pointer';

    $sizes = [
        'sm' => 'px-4 py-2 text-xs min-h-[38px]',
        'md' => 'px-6 py-3 text-xs sm:text-sm min-h-[44px]',
        'lg' => 'px-7 py-3.5 text-sm min-h-[48px]',
    ];

    $variants = [
        // Primary on dark surfaces: flat gold background + near-black text (10.8:1 AAA)
        'gold' => 'bg-gold text-ink hover:bg-gold/90 active:bg-gold/85 border border-gold',
        // Primary on light surfaces: graphite background + accessible gold text (9.1:1 AAA)
        'graphite' => 'bg-graphite text-gold hover:bg-ink active:bg-ink border border-graphite',
        // Secondary on dark surfaces
        'outline-light' => 'bg-transparent text-ivory border border-white/25 hover:border-gold hover:text-gold',
        // Secondary on light surfaces
        'outline-dark' => 'bg-transparent text-graphite border border-graphite/30 hover:border-graphite hover:bg-graphite/5',
        // Subtle link button
        'ghost' => 'bg-transparent text-current hover:underline px-2 py-1 min-h-[36px]',
    ];

    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['gold']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
