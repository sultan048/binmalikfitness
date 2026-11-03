@props([
    'title' => 'Dashboard — BIN MALIK FITNESS',
    'roleLabel' => 'Operations',
    'roleBadge' => 'Staff',
    'navigation' => [],
])
@php
    // Navigation items carrying a 'tab' key switch in-page panels (data-tab-panel); items without one stay plain links.
    $tabKeys = collect($navigation)->pluck('tab')->filter()->unique()->values()->all();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="min-h-screen bg-paper text-graphite surface-paper"
    x-data="{
        sidebarOpen: false,
        tabs: @js($tabKeys),
        tab: @js($tabKeys[0] ?? null),
        init() {
            if (! this.tabs.length) return;
            this.tab = this.tabFor(location.hash.slice(1)) || this.stored() || this.tabs[0];
            this.$nextTick(() => this.reveal(location.hash.slice(1)));
            window.addEventListener('hashchange', () => {
                const id = location.hash.slice(1);
                const t = this.tabFor(id);
                if (t) { this.tab = t; this.$nextTick(() => this.reveal(id)); }
            });
        },
        tabFor(id) {
            if (! id) return null;
            if (this.tabs.includes(id)) return id;
            const key = document.getElementById(id)?.closest('[data-tab-panel]')?.dataset.tabPanel;
            return this.tabs.includes(key) ? key : null;
        },
        reveal(id) {
            if (id && ! this.tabs.includes(id)) document.getElementById(id)?.scrollIntoView();
        },
        stored() {
            try {
                const t = sessionStorage.getItem('bmf-tab:' + location.pathname);
                return this.tabs.includes(t) ? t : null;
            } catch (e) { return null; }
        },
        setTab(t) {
            this.tab = t;
            this.sidebarOpen = false;
            try { sessionStorage.setItem('bmf-tab:' + location.pathname, t); } catch (e) {}
            history.replaceState(null, '', '#' + t);
            window.scrollTo({ top: 0 });
        },
    }"
>
    <x-site.prototype-bar />

    <div class="flex min-h-[calc(100vh-2rem)]">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:w-68 lg:flex-col lg:shrink-0 bg-ink text-ivory border-r border-white/10">
            <div class="flex h-16 items-center justify-between px-5 border-b border-white/10">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xs border border-gold bg-charcoal font-display text-lg text-gold">BM</span>
                    <div>
                        <span class="block font-display text-lg tracking-wider text-ivory leading-none">BIN MALIK FITNESS</span>
                        <span class="block text-[10px] uppercase tracking-widest text-gold">{{ $roleLabel }}</span>
                    </div>
                </a>
                <x-ui.badge tone="gold">{{ $roleBadge }}</x-ui.badge>
            </div>

            @auth
                <div class="border-b border-white/10 bg-charcoal/60 px-5 py-3 text-xs">
                    <p class="font-semibold text-ivory truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-silver truncate">{{ auth()->user()->email }}</p>
                    <p class="mt-1 text-[10px] uppercase tracking-wider text-gold">
                        Roles: {{ auth()->user()->getRoleNames()->implode(', ') ?: 'customer' }}
                    </p>
                </div>
            @endauth

            <nav aria-label="{{ $roleLabel }} Modules" class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @foreach ($navigation as $idx => $item)
                    @if (! empty($item['group']) && $item['group'] !== ($navigation[$idx - 1]['group'] ?? null))
                        <p class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-widest text-gold/80">{{ $item['group'] }}</p>
                    @endif

                    @if (! empty($item['tab']))
                        <button
                            type="button"
                            @click="setTab(@js($item['tab']))"
                            :class="tab === @js($item['tab']) ? 'bg-gold text-ink font-semibold' : 'text-silver hover:bg-white/5 hover:text-ivory'"
                            :aria-current="tab === @js($item['tab']) ? 'page' : false"
                            class="flex w-full cursor-pointer items-center justify-between rounded-xs px-3 py-2 text-left text-xs font-medium transition"
                        >
                            <span>{{ $item['label'] }}</span>
                            @if (isset($item['badge']) && $item['badge'] !== '')
                                <span
                                    :class="tab === @js($item['tab']) ? 'bg-ink text-gold' : 'bg-white/10 text-ivory'"
                                    class="rounded-xs px-1.5 py-0.5 text-[10px]"
                                >{{ $item['badge'] }}</span>
                            @endif
                        </button>
                    @else
                        <a
                            href="{{ $item['href'] ?? '#' }}"
                            class="flex items-center justify-between rounded-xs px-3 py-2 text-xs font-medium transition {{ ($idx === 0) ? 'bg-gold text-ink font-semibold' : 'text-silver hover:bg-white/5 hover:text-ivory' }}"
                        >
                            <span>{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))
                                <span class="rounded-xs px-1.5 py-0.5 text-[10px] {{ $idx === 0 ? 'bg-ink text-gold' : 'bg-white/10 text-ivory' }}">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-white/10 p-4 text-xs text-silver space-y-2">
                <a href="{{ route('home') }}" class="block text-ivory hover:text-gold">← Public Storefront</a>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left text-danger-on-dark hover:underline cursor-pointer">
                            Sign Out ({{ auth()->user()->email }})
                        </button>
                    </form>
                @endauth
            </div>
        </aside>

        <!-- Main Workspace -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-30 flex min-h-16 items-center justify-between gap-4 border-b border-graphite/12 bg-white px-4 py-3 sm:px-8">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden rounded-xs border border-graphite/20 px-2.5 py-1.5 text-xs font-semibold uppercase"
                    >
                        Modules
                    </button>
                    <div>
                        <h1 class="font-display text-2xl uppercase tracking-wide text-graphite leading-none">{{ $title }}</h1>
                        <p class="text-[11px] text-graphite/65">Authenticated Role Workspace · Backend Policies Enforced</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    @auth
                        <span class="hidden sm:inline text-xs font-semibold text-graphite">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-xs border border-graphite/25 px-3 py-1.5 text-xs font-semibold uppercase text-graphite hover:bg-graphite/5 cursor-pointer">
                                Sign Out
                            </button>
                        </form>
                    @endauth
                </div>
            </header>

            <div x-show="sidebarOpen" x-cloak class="border-b border-white/10 bg-ink p-4 text-ivory lg:hidden">
                <div class="grid grid-cols-2 gap-1.5 text-xs">
                    @foreach ($navigation as $idx => $item)
                        @if (! empty($item['group']) && $item['group'] !== ($navigation[$idx - 1]['group'] ?? null))
                            <p class="col-span-2 pt-2 text-[10px] font-semibold uppercase tracking-widest text-gold/80">{{ $item['group'] }}</p>
                        @endif

                        @if (! empty($item['tab']))
                            <button
                                type="button"
                                @click="setTab(@js($item['tab']))"
                                :class="tab === @js($item['tab']) ? 'bg-gold text-ink font-semibold' : 'bg-charcoal text-silver hover:text-gold'"
                                class="cursor-pointer rounded-xs px-2.5 py-2 text-left"
                            >{{ $item['label'] }}</button>
                        @else
                            <a href="{{ $item['href'] ?? '#' }}" class="rounded-xs bg-charcoal px-2.5 py-2 text-silver hover:text-gold">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <main id="main-content" class="flex-1 p-5 sm:p-8 space-y-6">
                @if (session('status'))
                    <div role="status" class="rounded-xs border border-success/40 bg-success/10 px-4 py-3 text-xs font-semibold text-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="rounded-xs border border-danger/40 bg-danger/10 px-4 py-3 text-xs text-danger space-y-1">
                        <p class="font-bold uppercase">Please correct the following errors:</p>
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
