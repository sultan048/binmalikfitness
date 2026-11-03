<?php

namespace App\Http\Middleware;

use App\Domain\Platform\AuditLogger;
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

        // Non-negotiable Rule #18: Demonstration users cannot authenticate in production
        if (app()->isProduction() && $user->is_demo) {
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

        return $next($request);
    }
}
