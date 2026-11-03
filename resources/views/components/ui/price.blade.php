@props([
    'paisa',
    'compareAtPaisa' => null,
    'surface' => 'dark', // dark | light
    'size' => 'md', // sm | md | lg
])

@php
    use App\Support\Money;
    $mainColor = $surface === 'light' ? 'text-graphite' : 'text-ivory';
    $strikeColor = $surface === 'light' ? 'text-graphite/55' : 'text-silver/70';
    $sizes = [
        'sm' => 'text-sm font-semibold',
        'md' => 'text-lg font-bold',
        'lg' => 'text-2xl sm:text-3xl font-bold',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-2 tabular-nums']) }}>
    <span class="{{ $sizes[$size] ?? $sizes['md'] }} {{ $mainColor }}">{{ Money::formatPaisa($paisa) }}</span>
    @if ($compareAtPaisa && $compareAtPaisa > $paisa)
        <span class="text-xs line-through {{ $strikeColor }}">{{ Money::formatPaisa($compareAtPaisa) }}</span>
    @endif
</span>
