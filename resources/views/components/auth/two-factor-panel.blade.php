@php
    $user = auth()->user();
    $totp = app(\App\Domain\Identity\TwoFactorTotpService::class);
    $unconfirmedSecret = ($user && $user->two_factor_secret && ! $user->two_factor_confirmed_at) ? $user->two_factor_secret : null;
@endphp

@if ($user)
    <section class="rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-display text-2xl uppercase text-graphite">Privileged Account Security (Optional TOTP 2FA)</h2>
                <p class="text-xs text-graphite/70">RFC 6238 Authenticator App + Single-Use Recovery Codes</p>
            </div>
            @if ($user->hasTwoFactorEnabled())
                <x-ui.badge tone="success">2FA Active & Confirmed</x-ui.badge>
            @elseif ($unconfirmedSecret)
                <x-ui.badge tone="gold">Pending Confirmation</x-ui.badge>
            @else
                <x-ui.badge tone="light">Not Enabled</x-ui.badge>
            @endif
        </div>

        @if ($user->hasTwoFactorEnabled())
            <div class="rounded-xs border border-graphite/12 bg-paper p-4 text-xs space-y-2">
                <p class="font-semibold text-graphite">Recovery Codes ({{ count($user->two_factor_recovery_codes ?? []) }} remaining):</p>
                <div class="flex flex-wrap gap-2 font-mono text-[11px]">
                    @foreach ($user->two_factor_recovery_codes ?? [] as $rc)
                        <span class="rounded-xs border border-graphite/15 bg-white px-2 py-0.5">{{ $rc }}</span>
                    @endforeach
                </div>
            </div>
            <form method="POST" action="{{ route('two-factor.disable') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label for="disable-2fa-pass" class="field-label text-xs">Confirm Password to Disable 2FA</label>
                    <input id="disable-2fa-pass" type="password" name="current_password" class="field-input" required />
                </div>
                <x-ui.button type="submit" variant="outline-dark" size="sm">Disable 2FA</x-ui.button>
            </form>
        @elseif ($unconfirmedSecret)
            <div class="rounded-xs border border-gold-dark/40 bg-paper p-4 text-xs space-y-2">
                <p class="font-bold text-gold-dark uppercase">Step 1: Add Secret to Authenticator App</p>
                <p class="font-mono text-sm font-bold text-graphite">{{ $unconfirmedSecret }}</p>
                @if (! app()->isProduction())
                    <p class="text-[11px] text-graphite/75">
                        Local testing helper — current 30s TOTP code: <code class="font-mono font-bold text-gold-dark">{{ $totp->currentCode($unconfirmedSecret) }}</code>
                    </p>
                @endif
            </div>
            <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label for="confirm-2fa-code" class="field-label text-xs">Step 2: Enter 6-Digit Verification Code</label>
                    <input id="confirm-2fa-code" type="text" name="code" class="field-input font-mono" placeholder="123456" required />
                </div>
                <x-ui.button type="submit" variant="graphite" size="sm">Confirm & Activate 2FA</x-ui.button>
            </form>
        @else
            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <x-ui.button type="submit" variant="graphite" size="sm">
                    Generate 2FA Secret & Recovery Codes
                </x-ui.button>
            </form>
        @endif
    </section>
@endif
