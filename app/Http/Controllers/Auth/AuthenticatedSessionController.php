<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Platform\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Demo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function showLogin(): View
    {
        return view('auth.login-register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->audit->record('auth.login.rate_limited', null, null, ['email' => $credentials['email']]);

            abort(429, "Too many login attempts. Please try again in {$seconds} seconds.");
        }

        $user = User::withTrashed()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->audit->record('auth.login.failed', $user, null, ['email' => $credentials['email']]);

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->is_demo && ! Demo::enabled()) {
            $this->audit->record('security.demo_account.blocked_in_production', $user, null, null, $user);
            abort(403, 'Demonstration accounts cannot authenticate when demo mode is off.');
        }

        if (! $user->isActive()) {
            $this->audit->record('auth.login.blocked_deactivated', $user, null, null, $user);

            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated. Please contact support.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        $this->audit->record('auth.login.succeeded', $user, null, null, $user);

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor_passed', false);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->put('two_factor_passed', true);

        return redirect()->intended($this->defaultDashboardRoute($user));
    }

    public function demoSwitch(Request $request): RedirectResponse
    {
        if (! Demo::enabled()) {
            abort(403, 'Demo role switching is disabled.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,manager,coach,customer'],
        ]);

        $emailMap = [
            'admin' => 'admin.demo@binmalikfitness.test',
            'manager' => 'manager.demo@binmalikfitness.test',
            'coach' => 'coach.demo@binmalikfitness.test',
            'customer' => 'customer.demo@binmalikfitness.test',
        ];

        $user = User::where('email', $emailMap[$validated['role']])->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('two_factor_passed', true);
        $user->update(['last_login_at' => now()]);

        $this->audit->record('auth.demo_switch.used', $user, null, ['role' => $validated['role']], $user);

        return redirect()->to($this->defaultDashboardRoute($user))
            ->with('status', 'Switched to demonstration role: '.strtoupper($validated['role']));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $this->audit->record('auth.logout', $user, null, null, $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been signed out securely.');
    }

    public function defaultDashboardRoute(User $user): string
    {
        if ($user->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            return route('manage.admin');
        }

        if ($user->hasAnyRole([RolePermissionMatrix::ROLE_MANAGER, RolePermissionMatrix::ROLE_COACH])) {
            return route('manage.manager');
        }

        return route('account.dashboard');
    }
}
