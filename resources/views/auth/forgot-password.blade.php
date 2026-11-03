<x-layouts.auth title="Password Recovery — BIN MALIK FITNESS">
    <div class="container-site">
        <div class="mx-auto max-w-lg rounded-sm border border-white/12 bg-charcoal p-6 sm:p-8 space-y-5">
            <div>
                <span class="eyebrow text-gold">Account Security</span>
                <h1 class="display-m text-ivory mt-1">Reset Your Password</h1>
                <p class="mt-2 text-xs text-silver leading-relaxed">
                    Enter the email address associated with your BIN MALIK FITNESS account. We will send a single-use password reset link.
                </p>
            </div>

            @if (session('status'))
                <div role="status" class="rounded-xs border border-gold bg-ink px-4 py-3 text-xs text-gold">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xs border border-danger-on-dark/50 bg-ink px-4 py-3 text-xs text-danger-on-dark">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="reset-email" class="field-label text-ivory">Email Address</label>
                    <input id="reset-email" type="email" name="email" value="{{ old('email') }}" class="field-input-dark" required />
                </div>
                <x-ui.button type="submit" variant="gold" class="w-full">
                    Send Password Reset Link
                </x-ui.button>
            </form>

            <div class="pt-2 text-center text-xs">
                <a href="{{ route('login') }}" class="text-silver hover:text-gold">← Return to Sign In</a>
            </div>
        </div>
    </div>
</x-layouts.auth>
