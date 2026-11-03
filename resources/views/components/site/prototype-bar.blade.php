@if (! app()->isProduction())
    <div class="border-b border-gold/25 bg-charcoal text-[11px] text-silver">
        <div class="container-site flex flex-wrap items-center justify-between gap-2 py-1.5">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-block h-2 w-2 rounded-full bg-gold"></span>
                <strong class="font-semibold uppercase tracking-wider text-ivory">Local/Testing Role Switcher:</strong>
                @foreach (['admin' => 'Admin', 'manager' => 'Manager', 'coach' => 'Coach (Staff)', 'customer' => 'Customer'] as $roleKey => $roleLabel)
                    <form method="POST" action="{{ route('demo.switch') }}" class="inline">
                        @csrf
                        <input type="hidden" name="role" value="{{ $roleKey }}" />
                        <button type="submit" class="rounded-xs border border-white/15 bg-ink px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-ivory hover:border-gold hover:text-gold cursor-pointer">
                            Switch → {{ $roleLabel }}
                        </button>
                    </form>
                @endforeach
            </div>
            <nav aria-label="Quick Navigation" class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <a href="{{ route('home') }}" class="hover:text-gold">Home</a>
                <a href="{{ route('shop.index') }}" class="hover:text-gold">Shop</a>
                <a href="{{ route('coaching.index') }}" class="hover:text-gold">Coaching</a>
                <a href="{{ route('login') }}" class="hover:text-gold">Auth</a>
                <a href="{{ route('design.system') }}" class="text-gold font-semibold hover:underline">Design System</a>
            </nav>
        </div>
    </div>
@endif
