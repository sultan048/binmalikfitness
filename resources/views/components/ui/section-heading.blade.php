@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'surface' => 'dark', // dark | light
    'align' => 'left', // left | center
])

@php
    $isLight = $surface === 'light';
    $eyebrowColor = $isLight ? 'text-gold-dark' : 'text-gold';
    $titleColor = $isLight ? 'text-graphite' : 'text-ivory';
    $subColor = $isLight ? 'text-graphite/75' : 'text-silver';
    $alignClass = $align === 'center' ? 'mx-auto text-center items-center' : 'text-left items-start';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 max-w-3xl '.$alignClass]) }}>
    @if ($eyebrow)
        <div class="flex items-center gap-3">
            <span class="h-0.5 w-7 bg-gold" aria-hidden="true"></span>
            <p class="eyebrow {{ $eyebrowColor }}">{{ $eyebrow }}</p>
        </div>
    @endif
    <h2 class="display-l {{ $titleColor }}">{{ $title }}</h2>
    @if ($subtitle)
        <p class="text-base sm:text-lg leading-relaxed {{ $subColor }}">{{ $subtitle }}</p>
    @endif
</div>
