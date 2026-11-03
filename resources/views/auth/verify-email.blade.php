<x-layouts.auth title="Verify Email Address — BIN MALIK FITNESS">
    <div class="container-site">
        <div class="mx-auto max-w-lg rounded-sm border border-white/12 bg-charcoal p-6 sm:p-8 space-y-5">
            <div>
                <span class="eyebrow text-gold">Email Verification</span>
                <h1 class="display-m text-ivory mt-1">Verify Your Email Address</h1>
                <p class="mt-2 text-xs text-silver leading-relaxed">
                    Please verify your email address by clicking the signed link sent to your inbox. If you did not receive the email, request a fresh link below.
                </p>
            </div>

            @if (session('status'))
                <div role="status" class="rounded-xs border border-gold bg-ink px-4 py-3 text-xs text-gold">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit" variant="gold" class="w-full">
                    Resend Verification Email
                </x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.auth>
