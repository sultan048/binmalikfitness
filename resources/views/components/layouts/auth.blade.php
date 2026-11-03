@props([
    'title' => 'Account Access — BIN MALIK FITNESS',
])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-ink text-ivory">
    <x-site.prototype-bar />
    <x-site.header />

    <main id="main-content" class="flex-1 py-12 sm:py-16">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
