<x-layouts.auth title="Set New Password — BIN MALIK FITNESS">
    <div class="container-site">
        <div class="mx-auto max-w-lg rounded-sm border border-white/12 bg-charcoal p-6 sm:p-8 space-y-5">
            <div>
                <span class="eyebrow text-gold">Single-Use Token Verified</span>
                <h1 class="display-m text-ivory mt-1">Choose a New Password</h1>
            </div>

            @if ($errors->any())
                <div role="alert" class="rounded-xs border border-danger-on-dark/50 bg-ink px-4 py-3 text-xs text-danger-on-dark">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}" />
                <div>
                    <label for="email" class="field-label text-ivory">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="field-input-dark" required />
                </div>
                <div>
                    <label for="password" class="field-label text-ivory">New Password (Min 8 chars)</label>
                    <input id="password" type="password" name="password" class="field-input-dark" required />
                </div>
                <div>
                    <label for="password_confirmation" class="field-label text-ivory">Confirm New Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="field-input-dark" required />
                </div>
                <x-ui.button type="submit" variant="gold" class="w-full">
                    Reset Password & Invalidate Old Sessions
                </x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.auth>
