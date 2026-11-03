<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Platform\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function notice(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
            $this->audit->record('auth.email.verified', $request->user(), null, null, $request->user());
        }

        return redirect()->route('account.dashboard')
            ->with('status', 'Your email address has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        $request->user()->sendEmailVerificationNotification();
        $this->audit->record('auth.email.verification_resent', $request->user(), null, null, $request->user());

        return back()->with('status', 'A fresh verification link has been sent to your email address.');
    }
}
