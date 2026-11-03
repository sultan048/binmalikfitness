<x-layouts.auth title="Two-Factor Authentication Challenge — BIN MALIK FITNESS">
    <div class="container-site">
        <div class="mx-auto max-w-lg rounded-sm border border-gold bg-charcoal p-6 sm:p-8 space-y-5">
            <div>
                <span class="eyebrow text-gold">Privileged Account Security</span>
                <h1 class="display-m text-ivory mt-1">Two-Factor Verification</h1>
                <p class="mt-2 text-xs text-silver leading-relaxed">
                    Enter the 6-digit TOTP code from your authenticator app or one of your single-use recovery codes to complete login.
                </p>
            </div>

            @if ($errors->any())
                <div role="alert" class="rounded-xs border border-danger-on-dark/50 bg-ink px-4 py-3 text-xs text-danger-on-dark">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="tfa-code" class="field-label text-ivory">6-Digit Code or Recovery Code (XXXX-XXXX)</label>
                    <input id="tfa-code" type="text" name="code" class="field-input-dark font-mono tracking-widest" placeholder="123456 or ABCD-EFGH" required autofocus />
                </div>
                <x-ui.button type="submit" variant="gold" class="w-full">
                    Verify & Continue
                </x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.auth>
