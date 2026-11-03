<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\TwoFactorTotpService;
use App\Domain\Platform\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorTotpService $totp,
        private readonly AuditLogger $audit,
    ) {}

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Re-provisioning would silently wipe an active 2FA setup; require the password-gated disable flow first.
        if ($user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                'code' => ['Two-Factor Authentication is already active. Disable it with your password before generating a new secret.'],
            ]);
        }

        $secret = $this->totp->generateSecret();
        $recoveryCodes = $this->totp->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => null,
        ]);

        $this->audit->record('auth.2fa.provisioned', $user, null, null, $user);

        return back()->with([
            'status' => 'Two-Factor Authentication secret generated. Enter the 6-digit verification code below to confirm.',
            'two_factor_setup_secret' => $secret,
            'two_factor_sample_code' => app()->isProduction() ? null : $this->totp->currentCode($secret),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret || ! $this->totp->verifyCode($user->two_factor_secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code was invalid.'],
            ]);
        }

        $user->update(['two_factor_confirmed_at' => now()]);
        $request->session()->put('two_factor_passed', true);

        $this->audit->record('auth.2fa.confirmed', $user, null, null, $user);

        return back()->with('status', 'Two-Factor Authentication is now active on your account.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password does not match.'],
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->audit->record('auth.2fa.disabled', $user, null, null, $user);

        return back()->with('status', 'Two-Factor Authentication has been disabled.');
    }

    public function showChallenge(Request $request): View
    {
        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, AuthenticatedSessionController $sessionCtrl): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();
        $code = trim($validated['code']);
        $verified = false;

        if ($user->two_factor_secret && $this->totp->verifyCode($user->two_factor_secret, $code)) {
            $verified = true;
        } else {
            $recoveryCodes = $user->two_factor_recovery_codes ?? [];
            $upper = strtoupper($code);
            foreach ($recoveryCodes as $recoveryCode) {
                if (hash_equals((string) $recoveryCode, $upper)) {
                    $verified = true;
                    $remaining = array_values(array_diff($recoveryCodes, [$recoveryCode]));
                    $user->update(['two_factor_recovery_codes' => $remaining]);
                    break;
                }
            }
        }

        if (! $verified) {
            $this->audit->record('auth.2fa.challenge_failed', $user, null, null, $user);

            throw ValidationException::withMessages([
                'code' => ['Invalid authentication code or recovery code.'],
            ]);
        }

        $request->session()->put('two_factor_passed', true);
        $this->audit->record('auth.2fa.challenge_passed', $user, null, null, $user);

        return redirect()->intended($sessionCtrl->defaultDashboardRoute($user));
    }
}
