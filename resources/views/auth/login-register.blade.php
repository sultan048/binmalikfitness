<x-layouts.auth title="Account Login & Registration — BIN MALIK FITNESS">
    <div class="container-site">
        <div class="mx-auto max-w-5xl space-y-8">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <span class="eyebrow text-gold">Secure Session, Rate-Limiting & Role-Aware Access</span>
                    <h1 class="display-l text-ivory mt-1">Member Login & Registration</h1>
                </div>
                <x-ui.badge tone="gold">Stage 2 Live Auth & RBAC</x-ui.badge>
            </div>

            @if (session('status'))
                <div role="status" class="rounded-xs border border-gold bg-charcoal px-4 py-3 text-xs font-semibold text-gold">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xs border border-danger-on-dark/50 bg-charcoal px-4 py-3 text-xs text-danger-on-dark space-y-1">
                    <p class="font-bold uppercase">Authentication / Validation Error:</p>
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                {{-- Sign In --}}
                <div class="rounded-sm border border-white/12 bg-charcoal p-6 sm:p-8 space-y-5">
                    <h2 class="font-display text-3xl text-ivory">Sign In to Your Account</h2>
                    <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="login-email" class="field-label text-ivory">Email Address</label>
                            <input id="login-email" type="email" name="email" value="{{ old('email') }}" class="field-input-dark" placeholder="you@example.com" required />
                        </div>
                        <div>
                            <label for="login-password" class="field-label text-ivory">Password</label>
                            <input id="login-password" type="password" name="password" class="field-input-dark" placeholder="••••••••••••" required />
                        </div>
                        <div class="flex items-center justify-between text-xs text-silver">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="remember" value="1" class="accent-gold" />
                                <span>Remember session</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="text-gold hover:underline">Forgot password?</a>
                        </div>
                        <x-ui.button type="submit" variant="gold" size="md" class="w-full">
                            Sign In Securely
                        </x-ui.button>
                    </form>

                    @if (\App\Support\Demo::enabled())
                        <div class="border-t border-white/10 pt-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-gold">Local/Testing One-Click Demo Login</span>
                                <x-ui.badge tone="demo">Blocked in Prod</x-ui.badge>
                            </div>
                            <p class="text-xs text-silver">
                                Authenticate directly into any seeded demonstration role to test permissions, policies, and isolation:
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ([
                                    'admin' => 'Demo Admin (Owner)',
                                    'manager' => 'Demo Manager + Coach',
                                    'coach' => 'Demo Coach (Staff)',
                                    'customer' => 'Demo Customer',
                                ] as $roleKey => $roleName)
                                    <form method="POST" action="{{ route('demo.switch') }}">
                                        @csrf
                                        <input type="hidden" name="role" value="{{ $roleKey }}" />
                                        <x-ui.button type="submit" variant="outline-light" size="sm" class="w-full">
                                            {{ $roleName }}
                                        </x-ui.button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Create Account --}}
                <div class="rounded-sm border border-white/12 bg-charcoal p-6 sm:p-8 space-y-5">
                    <h2 class="font-display text-3xl text-ivory">Create Customer Account</h2>
                    <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="_idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}" />
                        <div>
                            <label for="reg-name" class="field-label text-ivory">Full Name</label>
                            <input id="reg-name" type="text" name="name" value="{{ old('name') }}" class="field-input-dark" placeholder="Muhammad Ahmed" required />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="reg-email" class="field-label text-ivory">Email Address</label>
                                <input id="reg-email" type="email" name="email" value="{{ old('email') }}" class="field-input-dark" placeholder="ahmed@example.com" required />
                            </div>
                            <div>
                                <label for="reg-phone" class="field-label text-ivory">Mobile (03XX-XXXXXXX)</label>
                                <input id="reg-phone" type="tel" name="phone" value="{{ old('phone') }}" class="field-input-dark" placeholder="0300-1234567" required />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="reg-pass" class="field-label text-ivory">Password (Min 8 chars)</label>
                                <input id="reg-pass" type="password" name="password" class="field-input-dark" required />
                            </div>
                            <div>
                                <label for="reg-pass-confirm" class="field-label text-ivory">Confirm Password</label>
                                <input id="reg-pass-confirm" type="password" name="password_confirmation" class="field-input-dark" required />
                            </div>
                        </div>
                        <label class="flex items-start gap-2 text-xs text-silver cursor-pointer">
                            <input type="checkbox" name="terms" value="1" class="mt-0.5 accent-gold" required />
                            <span>I agree to the BIN MALIK FITNESS Terms, Privacy Policy, and Health & Coaching Disclaimer.</span>
                        </label>
                        <x-ui.button type="submit" variant="outline-light" size="md" class="w-full">
                            Register Customer Account
                        </x-ui.button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>
