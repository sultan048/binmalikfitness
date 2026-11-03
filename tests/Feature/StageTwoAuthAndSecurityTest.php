<?php

namespace Tests\Feature;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Identity\TwoFactorTotpService;
use App\Models\Address;
use App\Models\PrivateFile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StageTwoAuthAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_customer_registration_login_and_logout_flow_works_end_to_end(): void
    {
        $response = $this->post('/register', [
            '_idempotency_key' => (string) Str::uuid(),
            'name' => 'Bilal Khan',
            'email' => 'bilal.khan@example.com',
            'phone' => '0300-9876543',
            'password' => 'StrongPass#2026',
            'password_confirmation' => 'StrongPass#2026',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'bilal.khan@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole(RolePermissionMatrix::ROLE_CUSTOMER));
        $this->assertFalse($user->is_demo);
        $this->assertNotNull($user->customerProfile);
        $this->assertDatabaseHas('consent_records', [
            'user_id' => $user->id,
            'type' => 'terms_and_privacy',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'auth.user.registered',
        ]);

        // Logout invalidates session
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        // Login again
        $this->post('/login', [
            'email' => 'bilal.khan@example.com',
            'password' => 'StrongPass#2026',
        ])->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rate_limiting_blocks_brute_force_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'customer.demo@binmalikfitness.test',
                'password' => 'WrongPassword!',
            ])->assertSessionHasErrors('email');
        }

        $sixth = $this->post('/login', [
            'email' => 'customer.demo@binmalikfitness.test',
            'password' => 'WrongPassword!',
        ]);

        $sixth->assertStatus(429);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login.rate_limited',
        ]);
    }

    public function test_privilege_escalation_and_unauthorized_actions_are_blocked_in_backend(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $coach = User::where('email', 'coach.demo@binmalikfitness.test')->firstOrFail();
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();

        // 1. Customer cannot access staff dashboards
        $this->actingAs($customer)->get('/manage/admin')->assertForbidden();
        $this->actingAs($customer)->get('/manage/manager')->assertForbidden();

        // 2. Manager and Coach cannot access Admin dashboard
        $this->actingAs($manager)->get('/manage/admin')->assertForbidden();
        $this->actingAs($coach)->get('/manage/admin')->assertForbidden();

        // 3. Manager cannot modify roles or grant themselves Admin role
        $this->actingAs($manager)->put(route('manage.users.roles.update', $manager), [
            'roles' => ['admin'],
        ])->assertForbidden();

        $this->actingAs($manager)->put(route('manage.users.roles.update', $customer), [
            'roles' => ['manager'],
        ])->assertForbidden();

        // 4. Manager cannot modify unrestricted system settings
        $this->actingAs($manager)->put(route('manage.settings.update'), [
            'gym_city' => 'Unauthorized City',
            'gym_address' => 'Unauthorized Address',
            'gym_phone' => '0300-0000000',
        ])->assertForbidden();

        // 5. Manager cannot deactivate Admin or fellow Staff accounts
        $this->actingAs($manager)->put(route('manage.users.status.update', $admin), [
            'status' => 'deactivated',
        ])->assertForbidden();

        $this->actingAs($manager)->put(route('manage.users.status.update', $coach), [
            'status' => 'deactivated',
        ])->assertForbidden();

        // 6. Coach-only user cannot assign coaches
        $this->actingAs($coach)->post(route('manage.coach-assignments.store'), [
            'coach_id' => $coach->id,
            'client_id' => $customer->id,
            'context' => 'coaching',
        ])->assertForbidden();

        // 7. Admin cannot change their own role or deactivate their own account
        $this->actingAs($admin)->put(route('manage.users.roles.update', $admin), [
            'roles' => ['customer'],
        ])->assertForbidden();

        $this->actingAs($admin)->put(route('manage.users.status.update', $admin), [
            'status' => 'deactivated',
        ])->assertForbidden();
    }

    public function test_cross_account_isolation_and_coach_assignment_scoping(): void
    {
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $assignedCustomer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $unassignedCustomer = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();

        // Manager CAN view confidential file of assigned customer
        $this->actingAs($manager)
            ->get(route('manage.clients.confidential', $assignedCustomer))
            ->assertOk()
            ->assertSee($assignedCustomer->name);

        // Manager CANNOT view confidential file of unassigned customer
        $this->actingAs($manager)
            ->get(route('manage.clients.confidential', $unassignedCustomer))
            ->assertForbidden();

        // Customer B CANNOT delete Customer A's address
        $addressOfAssigned = Address::where('user_id', $assignedCustomer->id)->firstOrFail();
        $this->actingAs($unassignedCustomer)
            ->delete(route('account.addresses.destroy', $addressOfAssigned))
            ->assertForbidden();

        $this->assertDatabaseHas('addresses', ['id' => $addressOfAssigned->id]);
    }

    public function test_password_reset_safety_and_single_use_token_invalidation(): void
    {
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $token = Password::broker()->createToken($customer);

        // Reset password using valid token
        $res = $this->post('/reset-password', [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'UpdatedSecure#99',
            'password_confirmation' => 'UpdatedSecure#99',
        ]);

        $res->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('UpdatedSecure#99', $customer->fresh()->password));

        // Replaying the same reset token must fail
        $replay = $this->from("/reset-password/{$token}")->post('/reset-password', [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'AnotherPassword#88',
            'password_confirmation' => 'AnotherPassword#88',
        ]);

        $replay->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('AnotherPassword#88', $customer->fresh()->password));
    }

    public function test_session_invalidation_on_deactivation_and_demo_account_blocked_in_production(): void
    {
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();

        // Log in as customer, then deactivate account -> next request logs out and redirects to /login
        $this->actingAs($customer)->get('/account')->assertOk();
        $customer->update(['status' => 'deactivated']);

        $this->actingAs($customer)->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();

        // Reactivate customer, then switch app env to production -> demo account blocked with 403
        $customer->update(['status' => 'active']);
        $this->app['env'] = 'production';

        $this->withSession(['_token' => 'prod-csrf-token'])
            ->post('/demo-login', ['_token' => 'prod-csrf-token', 'role' => 'admin'])
            ->assertForbidden();
        $this->actingAs($customer)->get('/account')->assertForbidden();
    }

    public function test_duplicate_form_submission_with_same_idempotency_key_is_rejected(): void
    {
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $idemKey = 'addr-submit-'.Str::uuid();

        $payload = [
            '_idempotency_key' => $idemKey,
            'label' => 'Office',
            'recipient_name' => 'Demo Coaching Member',
            'phone' => '0300-0000003',
            'line1' => 'Plot 22, Gulberg III',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ];

        $this->actingAs($customer)->post(route('account.addresses.store'), $payload)->assertRedirect();
        $this->actingAs($customer)->post(route('account.addresses.store'), $payload)->assertStatus(409);

        $this->assertSame(1, Address::where('user_id', $customer->id)->where('label', 'Office')->count());
    }

    public function test_file_upload_validation_and_private_file_authorization(): void
    {
        Storage::fake('local');

        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $assignedCustomer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $unassignedCustomer = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();

        // 1. Reject PHP / script file upload
        $malicious = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');
        $this->actingAs($assignedCustomer)->post(route('account.private-files.store'), [
            'category' => 'intake_document',
            'consent_granted' => '1',
            'file' => $malicious,
        ])->assertSessionHasErrors('file');

        // 2. Accept valid PDF upload with explicit consent
        $validPdf = UploadedFile::fake()->create('bloodwork.pdf', 120, 'application/pdf');
        $this->actingAs($assignedCustomer)->post(route('account.private-files.store'), [
            'category' => 'intake_document',
            'consent_granted' => '1',
            'file' => $validPdf,
        ])->assertRedirect();

        $privateFile = PrivateFile::where('owner_id', $assignedCustomer->id)->firstOrFail();
        Storage::disk('local')->assertExists($privateFile->path);

        // 3. Owner, Admin, and Assigned Coach/Manager can stream the private file (200)
        $this->actingAs($assignedCustomer)->get(route('private-files.download', $privateFile))->assertOk();
        $this->actingAs($admin)->get(route('private-files.download', $privateFile))->assertOk();
        $this->actingAs($manager)->get(route('private-files.download', $privateFile))->assertOk();

        // 4. Another Customer cannot stream the private file (403)
        $this->actingAs($unassignedCustomer)->get(route('private-files.download', $privateFile))->assertForbidden();

        // 5. If a file belongs to an unassigned customer, Manager is denied (403)
        $unassignedPdf = UploadedFile::fake()->create('private-notes.pdf', 80, 'application/pdf');
        $this->actingAs($unassignedCustomer)->post(route('account.private-files.store'), [
            'category' => 'intake_document',
            'consent_granted' => '1',
            'file' => $unassignedPdf,
        ])->assertRedirect();

        $unassignedFile = PrivateFile::where('owner_id', $unassignedCustomer->id)->firstOrFail();
        $this->actingAs($manager)->get(route('private-files.download', $unassignedFile))->assertForbidden();
    }

    public function test_optional_two_factor_authentication_flow_for_privileged_users(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $totp = app(TwoFactorTotpService::class);

        // 1. Provision 2FA secret
        $this->actingAs($admin)->post(route('two-factor.enable'))->assertRedirect();
        $admin->refresh();
        $this->assertNotNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);

        // 2. Confirm 2FA using valid TOTP code
        $validCode = $totp->currentCode($admin->two_factor_secret);
        $this->actingAs($admin)->post(route('two-factor.confirm'), [
            'code' => $validCode,
        ])->assertRedirect();

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactorEnabled());

        // 3. When session has not passed 2FA challenge, accessing /manage/admin redirects to challenge
        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => false])
            ->get('/manage/admin')
            ->assertRedirect(route('two-factor.challenge'));

        // 4. Completing challenge with valid TOTP code grants access
        $this->actingAs($admin)
            ->withSession(['two_factor_passed' => false])
            ->post(route('two-factor.verify'), [
                'code' => $totp->currentCode($admin->two_factor_secret),
            ])
            ->assertRedirect(route('manage.admin'));
    }
}
