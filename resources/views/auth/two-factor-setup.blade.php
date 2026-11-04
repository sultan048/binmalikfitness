<x-layouts.auth title="Set Up Two-Factor Authentication — BIN MALIK FITNESS">
    <div class="container-site">
        <div class="mx-auto max-w-2xl space-y-5">
            <div>
                <span class="eyebrow text-gold">Account Security</span>
                <h1 class="display-m text-ivory mt-1">Two-Factor Authentication</h1>
                @if ($mandatory)
                    <p class="mt-2 text-xs text-silver leading-relaxed">
                        Staff accounts can open the back office only after 2FA is active. Generate a secret, add it to an authenticator app
                        (Google Authenticator, Microsoft Authenticator, 1Password, etc.) and confirm with the 6-digit code.
                    </p>
                @endif
            </div>

            @if (session('status'))
                <div role="status" class="rounded-xs border border-gold bg-ink px-4 py-3 text-xs text-gold">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xs border border-danger/40 bg-danger/10 px-4 py-3 text-xs text-danger-on-dark">
                    @foreach ($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <div class="text-graphite">
                <x-auth.two-factor-panel />
            </div>

            @if (auth()->user()->hasTwoFactorEnabled())
                <x-ui.button :href="app(\App\Http\Controllers\Auth\AuthenticatedSessionController::class)->defaultDashboardRoute(auth()->user())" variant="gold" class="w-full">
                    Continue to Dashboard
                </x-ui.button>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="text-xs text-silver underline hover:text-ivory cursor-pointer">Sign out</button>
            </form>
        </div>
    </div>
</x-layouts.auth>
