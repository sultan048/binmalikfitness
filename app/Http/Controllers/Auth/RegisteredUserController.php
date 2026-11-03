<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Platform\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\ConsentRecord;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:160', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{10,20}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'status' => 'active',
                'is_demo' => false,
                'last_login_at' => now(),
            ]);

            $newUser->assignRole(RolePermissionMatrix::ROLE_CUSTOMER);

            CustomerProfile::create([
                'user_id' => $newUser->id,
                'fitness_goal' => 'General Fitness & Strength',
            ]);

            ConsentRecord::create([
                'user_id' => $newUser->id,
                'type' => 'terms_and_privacy',
                'granted_at' => now(),
                'evidence_note' => 'Accepted Terms, Privacy Policy & Health Disclaimer on registration.',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            $this->audit->record('auth.user.registered', $newUser, null, [
                'email' => $newUser->email,
                'role' => RolePermissionMatrix::ROLE_CUSTOMER,
            ], $newUser);

            return $newUser;
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('two_factor_passed', true);

        return redirect()->route('account.dashboard')
            ->with('status', 'Your BIN MALIK FITNESS account has been created.');
    }
}
