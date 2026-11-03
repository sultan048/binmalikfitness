@props([
    'label' => 'Demo Data',
])

@if (! app()->isProduction())
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-xs border border-gold/40 bg-charcoal px-2 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-gold']) }} title="Demonstration data — will be replaced with verified owner content before launch">
        <span class="h-1.5 w-1.5 rounded-full bg-gold" aria-hidden="true"></span>
        {{ $label }}
    </span>
@endif
