<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Platform\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\ConsentRecord;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
