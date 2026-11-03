@props([
    'tone' => 'dark', // gold | dark | light | success | danger | demo
])

@php
    $tones = [
        'gold' => 'bg-gold text-ink border-gold',
        'dark' => 'bg-white/10 text-ivory border-white/15',
        'light' => 'bg-graphite/8 text-graphite border-graphite/15',
        'success' => 'bg-success text-white border-success',
        'danger' => 'bg-danger text-white border-danger',
        'demo' => 'bg-charcoal text-gold border-gold/40',
    ];
    $classes = 'inline-flex items-center gap-1.5 rounded-xs border px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider '.($tones[$tone] ?? $tones['dark']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
