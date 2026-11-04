<?php

namespace App\Http\Middleware;

use App\Domain\Platform\AuditLogger;
use App\Support\Demo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActiveAndPermitted
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Non-negotiable Rule #18: Demonstration users cannot authenticate in production (or with demo mode off)
        if ($user->is_demo && ! Demo::enabled()) {
            $this->audit->record('security.demo_account.blocked_in_production', $user, null, null, $user);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Demonstration accounts are disabled in production.');
        }

        // Enforce active account status on every authenticated request
        if (! $user->isActive()) {
            $this->audit->record('security.deactivated_account.session_terminated', $user, null, null, $user);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Your account has been deactivated.');
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ]);
        }

        // Enforce 2FA challenge if user has confirmed 2FA and session has not completed challenge
        if (
            $user->hasTwoFactorEnabled()
            && ! $request->session()->get('two_factor_passed', false)
            && ! $request->routeIs('two-factor.challenge', 'two-factor.verify', 'logout')
        ) {
            return redirect()->route('two-factor.challenge');
        }

        // Staff must have 2FA active before using anything else (setup, verification and logout stay reachable)
        if (
            config('auth.require_staff_two_factor')
            && ! $user->hasTwoFactorEnabled()
            && $user->isPrivilegedStaff()
            && ! $request->routeIs('two-factor.*', 'logout', 'verification.*')
        ) {
            if ($request->expectsJson()) {
                abort(403, 'Two-factor authentication must be enabled for staff accounts.');
            }

            return redirect()->route('two-factor.setup')
                ->with('status', 'Staff accounts must enable two-factor authentication before continuing.');
        }

        return $next($request);
    }
}
