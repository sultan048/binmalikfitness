@props([
    'title' => 'BIN MALIK FITNESS — Online Coaching, Supplements & Strength Gym in Pakistan',
    'description' => '1-on-1 online fitness coaching, Pakistani-kitchen diet plans, batch-tracked supplements in PKR, and strength gym memberships.',
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
    'robots' => 'index,follow',
    'jsonLdSchemas' => [],
])
@php
    $resolvedCanonical = $canonical ?: url()->current();
    $resolvedOgImage = $ogImage ?: url('/images/demo/hero-training.png');
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth motion-reduce:scroll-auto">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}" />
    <meta name="robots" content="{{ $robots }}" />
    <link rel="canonical" href="{{ $resolvedCanonical }}" />

    {{-- Open Graph & Social Sharing Metadata --}}
    <meta property="og:site_name" content="BIN MALIK FITNESS" />
    <meta property="og:locale" content="en_PK" />
    <meta property="og:type" content="{{ $ogType }}" />
    <meta property="og:title" content="{{ $title }}" />
    <meta property="og:description" content="{{ $description }}" />
    <meta property="og:url" content="{{ $resolvedCanonical }}" />
    <meta property="og:image" content="{{ $resolvedOgImage }}" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $title }}" />
    <meta name="twitter:description" content="{{ $description }}" />
    <meta name="twitter:image" content="{{ $resolvedOgImage }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @foreach ($jsonLdSchemas as $schemaBlock)
        @if (! empty($schemaBlock))
            <script type="application/ld+json">{!! json_encode($schemaBlock, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif
    @endforeach
</head>
<body class="min-h-screen flex flex-col bg-ink text-ivory">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-gold focus:px-4 focus:py-2 focus:text-xs focus:font-bold focus:uppercase focus:text-ink">
        Skip to main content
    </a>

    <x-site.prototype-bar />
    <x-site.header />

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />
    <x-site.whatsapp-float />
</body>
</html>
