@php
    $navLinks = [
        ['label' => 'Online Coaching', 'href' => route('coaching.index'), 'active' => request()->routeIs('coaching.*')],
        ['label' => 'Diet Plans', 'href' => route('diet-plans.show', 'pakistani-fat-loss-blueprint-demo'), 'active' => request()->routeIs('diet-plans.*')],
        ['label' => 'Supplement Shop', 'href' => route('shop.index'), 'active' => request()->routeIs('shop.*') || request()->routeIs('products.*')],
        ['label' => 'Track Order', 'href' => route('orders.track'), 'active' => request()->routeIs('orders.*')],
        ['label' => 'Physical Gym', 'href' => route('gym.index'), 'active' => request()->routeIs('gym.*')],
    ];

    $headerCartCount = 0;
    try {
        $headerCart = app(\App\Domain\Commerce\CartService::class)->resolveCart(request(), false);
        $headerCartCount = $headerCart ? (int) $headerCart->items()->sum('quantity') : 0;
    } catch (\Throwable) {
        $headerCartCount = 0;
    }
@endphp

<header
    x-data="{ mobileOpen: false }"
    class="sticky top-0 z-40 border-b border-white/10 bg-ink/95 backdrop-blur-md"
>
    <div class="container-site flex h-18 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3 group" aria-label="BIN MALIK FITNESS Home">
            <span class="flex h-10 w-10 items-center justify-center rounded-xs border border-gold bg-charcoal font-display text-2xl tracking-wider text-gold">
                BM
            </span>
            <div class="leading-none">
                <span class="block font-display text-2xl tracking-wider text-ivory group-hover:text-gold transition">
                    BIN MALIK FITNESS
                </span>
                <span class="block text-[10px] font-semibold uppercase tracking-[0.22em] text-silver">
                    Coaching · Supplements · Strength Gym
                </span>
            </div>
        </a>

        <nav aria-label="Primary Navigation" class="hidden lg:flex items-center gap-7">
            @foreach ($navLinks as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="text-xs font-semibold uppercase tracking-wider transition {{ $item['active'] ? 'text-gold' : 'text-ivory/85 hover:text-gold' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Permission-Aware Account & Role Navigation --}}
        <div class="hidden lg:flex items-center gap-3">
            <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-1.5 rounded-xs border border-white/15 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-ivory hover:border-gold hover:text-gold">
                <span>Cart</span>
                <span class="rounded-xs bg-gold px-1.5 py-0.2 text-[11px] font-bold text-ink">{{ $headerCartCount }}</span>
            </a>

            @auth
                @role('admin')
                    <a href="{{ route('manage.admin') }}" class="rounded-xs border border-gold/50 bg-charcoal px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gold hover:bg-gold hover:text-ink">
                        Admin Panel
                    </a>
                @endrole

                @hasanyrole('manager|coach')
                    <a href="{{ route('manage.manager') }}" class="rounded-xs border border-gold/50 bg-charcoal px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gold hover:bg-gold hover:text-ink">
                        Staff Workspace
                    </a>
                @endhasanyrole

                <a href="{{ route('account.dashboard') }}" class="px-2.5 py-2 text-xs font-semibold uppercase tracking-wider text-ivory hover:text-gold">
                    {{ auth()->user()->name }}
                </a>

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-2.5 py-2 text-xs font-semibold uppercase tracking-wider text-silver hover:text-danger-on-dark cursor-pointer">
                        Sign Out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-silver hover:text-ivory">
                    Login / Register
                </a>
                <x-ui.button :href="route('coaching.index')" variant="gold" size="sm">
                    Start Online Coaching
                </x-ui.button>
            @endauth
        </div>

        <div class="flex items-center gap-2 lg:hidden">
            <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-1 rounded-xs border border-white/15 px-2.5 py-2 text-xs font-semibold text-ivory">
                Cart <span class="rounded-xs bg-gold px-1 text-[10px] font-bold text-ink">{{ $headerCartCount }}</span>
            </a>
            <button
                type="button"
                @click="mobileOpen = !mobileOpen"
                :aria-expanded="mobileOpen.toString()"
                aria-controls="mobile-nav-drawer"
                aria-label="Toggle Menu"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xs border border-white/20 text-ivory hover:border-gold"
            >
                <svg x-show="!mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="square" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
                <svg x-show="mobileOpen" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="square" d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>
        </div>
    </div>

    <div
        id="mobile-nav-drawer"
        x-show="mobileOpen"
        x-cloak
        x-collapse
        class="border-t border-white/10 bg-charcoal lg:hidden"
    >
        <div class="container-site space-y-4 py-5">
            <nav aria-label="Mobile Navigation" class="flex flex-col space-y-2.5">
                @foreach ($navLinks as $item)
                    <a href="{{ $item['href'] }}" class="py-1.5 text-sm font-semibold uppercase tracking-wider text-ivory hover:text-gold">
                        {{ $item['label'] }}
                    </a>
                @endforeach
                @auth
                    @role('admin')
                        <a href="{{ route('manage.admin') }}" class="py-1.5 text-sm font-semibold uppercase tracking-wider text-gold">Admin Control Center</a>
                    @endrole
                    @hasanyrole('manager|coach')
                        <a href="{{ route('manage.manager') }}" class="py-1.5 text-sm font-semibold uppercase tracking-wider text-gold">Staff Workspace</a>
                    @endhasanyrole
                    <a href="{{ route('account.dashboard') }}" class="py-1.5 text-sm font-semibold uppercase tracking-wider text-ivory">My Account ({{ auth()->user()->name }})</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="py-1.5 text-sm font-semibold uppercase tracking-wider text-danger-on-dark">Sign Out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="py-1.5 text-sm font-semibold uppercase tracking-wider text-silver hover:text-ivory">
                        Login / Register
                    </a>
                @endauth
            </nav>
            <div class="grid grid-cols-1 gap-2.5 pt-2 sm:grid-cols-2">
                <x-ui.button :href="route('coaching.index')" variant="gold" size="md">
                    Start Online Coaching
                </x-ui.button>
                <x-ui.button :href="route('shop.index')" variant="outline-light" size="md">
                    Shop Supplements
                </x-ui.button>
            </div>
        </div>
    </div>
</header>
