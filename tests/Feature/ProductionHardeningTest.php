<?php

namespace Tests\Feature;

use App\Domain\Identity\TwoFactorTotpService;
use App\Enums\OrderStatus;
use App\Models\CoachAssignment;
use App\Models\CoachingCheckIn;
use App\Models\ConsentRecord;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderUpdateNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the production-readiness and security changes: demo mode, e-mail verification,
 * 2FA hardening, order access, coach-assignment approval, rate limits, CSP, notifications,
 * exports and the tabbed dashboards.
 */
class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_demo_features_disappear_when_demo_mode_is_off(): void
    {
        $this->get('/')->assertOk()->assertSee('Role Switcher');
        $this->get('/design-system')->assertOk();

        config(['app.demo_mode' => false]);

        $this->get('/')->assertOk()->assertDontSee('Role Switcher');
        $this->get('/design-system')->assertNotFound();
        $this->post('/demo-login', ['role' => 'admin'])->assertForbidden();

        // Seeded demo accounts can no longer use the app
        $this->actingAs($this->user('customer.demo@binmalikfitness.test'))->get('/account')->assertForbidden();
    }

    public function test_unverified_customers_must_verify_their_email(): void
    {
        $user = User::factory()->unverified()->create(['status' => 'active']);
        $user->assignRole('customer');

        $this->actingAs($user)->get('/account')->assertRedirect(route('verification.notice'));

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($user)->get('/account')->assertOk();
    }

    public function test_two_factor_cannot_be_reprovisioned_and_recovery_codes_are_shown_once(): void
    {
        $admin = $this->user('admin.demo@binmalikfitness.test');
        $totp = app(TwoFactorTotpService::class);

        $this->actingAs($admin)->post(route('two-factor.enable'))->assertRedirect();
        $admin->refresh();

        $this->actingAs($admin)
            ->post(route('two-factor.confirm'), ['code' => $totp->currentCode($admin->two_factor_secret)])
            ->assertSessionHas('two_factor_recovery_codes');

        $admin->refresh();
        $secret = $admin->two_factor_secret;
        $firstCode = $admin->two_factor_recovery_codes[0];

        // Re-running "enable" while active must not wipe the confirmed secret
        $this->actingAs($admin)->post(route('two-factor.enable'))->assertSessionHasErrors('code');
        $admin->refresh();
        $this->assertSame($secret, $admin->two_factor_secret);
        $this->assertTrue($admin->hasTwoFactorEnabled());

        // Later visits show only the count, never the codes
        $this->actingAs($admin)->withSession(['two_factor_passed' => true])
            ->get(route('two-factor.setup'))
            ->assertOk()
            ->assertDontSee($firstCode);

        // Regenerating requires the password and replaces the codes
        $this->actingAs($admin)->withSession(['two_factor_passed' => true])
            ->post(route('two-factor.recovery-codes'), ['current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($admin)->withSession(['two_factor_passed' => true])
            ->post(route('two-factor.recovery-codes'), ['current_password' => 'DemoOnly!NotForProd#1'])
            ->assertSessionHas('two_factor_recovery_codes');
        $this->assertNotContains($firstCode, $admin->fresh()->two_factor_recovery_codes);
    }

    public function test_two_factor_challenge_is_rate_limited(): void
    {
        $admin = $this->user('admin.demo@binmalikfitness.test');
        $admin->forceFill([
            'two_factor_secret' => app(TwoFactorTotpService::class)->generateSecret(),
            'two_factor_confirmed_at' => now(),
        ])->save();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin)->withSession(['two_factor_passed' => false])
                ->post(route('two-factor.verify'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->actingAs($admin)->withSession(['two_factor_passed' => false])
            ->post(route('two-factor.verify'), ['code' => '000000'])
            ->assertStatus(429);
    }

    public function test_staff_without_two_factor_are_sent_to_setup_when_required(): void
    {
        config(['auth.require_staff_two_factor' => true]);
        $manager = $this->user('manager.demo@binmalikfitness.test');

        $this->actingAs($manager)->get('/manage/manager')->assertRedirect(route('two-factor.setup'));
        $this->actingAs($manager)->get(route('two-factor.setup'))->assertOk()->assertSee('Two-Factor Authentication');

        // Customers are not affected
        $this->actingAs($this->user('customer.demo@binmalikfitness.test'))->get('/account')->assertOk();
    }

    public function test_payment_proof_is_rejected_for_a_cancelled_order(): void
    {
        Storage::fake('local');
        $customer = $this->user('customer.demo@binmalikfitness.test');
        $whey = ProductVariant::where('sku', 'DEMO-WPI-5LB-VAN')->firstOrFail();

        $this->actingAs($customer)->post('/cart/items', ['product_variant_id' => $whey->id, 'quantity' => 1]);
        $this->actingAs($customer)->post('/checkout', [
            '_idempotency_key' => 'bt-'.Str::uuid(),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'saved_address_id' => $customer->addresses()->first()->id,
            'shipping_method' => 'standard_courier',
            'payment_method' => 'bank_transfer',
        ]);

        $order = Order::where('user_id', $customer->id)->latest('id')->firstOrFail();
        $this->actingAs($customer)->post(route('orders.cancel', $order->order_number), ['reason' => 'Changed my mind'])->assertRedirect();
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);

        $this->actingAs($customer)->post(route('orders.payment-proof.store', $order->order_number), [
            'transaction_reference' => 'LATE-TXN-1',
            'sender_bank_name' => 'Meezan Bank',
            'receipt_file' => UploadedFile::fake()->create('receipt.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors('payment');

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_order_status_transitions_follow_the_state_machine(): void
    {
        $this->assertTrue(OrderStatus::Processing->canTransitionTo(OrderStatus::Shipped));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::Processing));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Shipped));

        $manager = $this->user('manager.demo@binmalikfitness.test');
        $delivered = Order::where('order_number', 'BMF-DEMO-1001')->firstOrFail();
        $this->assertSame(Order::STATUS_DELIVERED, $delivered->status);

        $this->actingAs($manager)
            ->put(route('manage.orders.status.update', $delivered), ['status' => Order::STATUS_PROCESSING])
            ->assertSessionHasErrors('status');
        $this->assertSame(Order::STATUS_DELIVERED, $delivered->fresh()->status);
    }

    public function test_guest_order_notification_is_queued_and_order_number_is_unguessable(): void
    {
        Notification::fake();
        $shaker = ProductVariant::where('sku', 'DEMO-ACC-SHK700-BLK')->firstOrFail();

        $this->post('/cart/items', ['product_variant_id' => $shaker->id, 'quantity' => 1]);
        $this->post('/checkout', [
            '_idempotency_key' => 'guest-'.Str::uuid(),
            'customer_name' => 'Real Customer',
            'customer_email' => 'real.customer@example.com',
            'customer_phone' => '0300-1112223',
            'line1' => 'House 1, Street 2',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'shipping_method' => 'standard_courier',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::where('customer_email', 'real.customer@example.com')->firstOrFail();
        $this->assertMatchesRegularExpression('/^BMF-\d{6}-[A-Z0-9]{8}$/', $order->order_number);

        Notification::assertSentOnDemand(
            OrderUpdateNotification::class,
            fn (OrderUpdateNotification $n, array $channels, object $notifiable) => $n->event === OrderUpdateNotification::EVENT_PLACED
                && $notifiable->routes['mail'] === 'real.customer@example.com'
        );
    }

    public function test_manager_coach_assignments_need_admin_approval(): void
    {
        $admin = $this->user('admin.demo@binmalikfitness.test');
        $manager = $this->user('manager.demo@binmalikfitness.test');
        $coach = $this->user('coach.demo@binmalikfitness.test');
        $client = $this->user('unassigned.customer@binmalikfitness.test');

        // A manager cannot assign themselves
        $this->actingAs($manager)->post(route('manage.coach-assignments.store'), [
            'coach_id' => $manager->id,
            'client_id' => $client->id,
            'context' => 'coaching',
        ])->assertForbidden();

        // A manager's assignment of another coach waits for approval and grants nothing yet
        $this->actingAs($manager)->post(route('manage.coach-assignments.store'), [
            'coach_id' => $coach->id,
            'client_id' => $client->id,
            'context' => 'coaching',
        ])->assertRedirect();

        $assignment = CoachAssignment::where('coach_id', $coach->id)->where('client_id', $client->id)->latest('id')->firstOrFail();
        $this->assertSame(CoachAssignment::STATUS_PENDING, $assignment->status);
        $this->assertFalse($coach->fresh()->isAssignedToClient($client->id));

        // Only an admin can approve
        $this->actingAs($manager)->post(route('manage.coach-assignments.approve', $assignment))->assertForbidden();
        $this->actingAs($admin)->post(route('manage.coach-assignments.approve', $assignment))->assertRedirect();

        $this->assertSame(CoachAssignment::STATUS_APPROVED, $assignment->fresh()->status);
        $this->assertTrue($coach->fresh()->isAssignedToClient($client->id));
    }

    public function test_updating_a_row_does_not_overwrite_event_timestamps(): void
    {
        $consent = ConsentRecord::create([
            'user_id' => $this->user('customer.demo@binmalikfitness.test')->id,
            'type' => 'marketing',
            'granted_at' => now()->subDays(10)->startOfSecond(),
        ]);
        $grantedAt = $consent->fresh()->granted_at->toDateTimeString();

        $consent->update(['withdrawn_at' => now()]);

        $this->assertSame($grantedAt, $consent->fresh()->granted_at->toDateTimeString());
    }

    public function test_health_notes_are_encrypted_at_rest(): void
    {
        $checkIn = CoachingCheckIn::create([
            'user_id' => $this->user('customer.demo@binmalikfitness.test')->id,
            'check_in_date' => now()->toDateString(),
            'weight_kg' => 80,
            'client_notes' => 'Knee pain after squats',
        ]);

        $raw = DB::table('coaching_check_ins')->where('id', $checkIn->id)->value('client_notes');
        $this->assertStringNotContainsString('Knee pain', $raw);
        $this->assertSame('Knee pain after squats', $checkIn->fresh()->client_notes);
    }

    public function test_content_security_policy_blocks_inline_and_eval_scripts(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_settings_are_cached_and_refreshed_after_changes(): void
    {
        Setting::updateOrCreate(['key' => 'gym.city'], ['group' => 'gym', 'value' => 'Lahore']);
        $this->assertSame('Lahore', Setting::getValue('gym.city'));

        Setting::updateOrCreate(['key' => 'gym.city'], ['group' => 'gym', 'value' => 'Multan']);
        $this->assertSame('Multan', Setting::getValue('gym.city'));
        $this->assertSame('fallback', Setting::getValue('missing.key', 'fallback'));
    }

    public function test_csv_exports_are_limited_to_admin_and_manager(): void
    {
        $this->actingAs($this->user('admin.demo@binmalikfitness.test'))
            ->get(route('manage.exports.csv', 'customers'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->user('coach.demo@binmalikfitness.test'))
            ->get(route('manage.exports.csv', 'customers'))
            ->assertForbidden();

        $this->actingAs($this->user('customer.demo@binmalikfitness.test'))
            ->get(route('account.orders.export'))
            ->assertOk()
            ->assertDownload();
    }

    public function test_every_dashboard_tab_has_a_matching_panel(): void
    {
        $pages = [
            ['customer.demo@binmalikfitness.test', '/account'],
            ['manager.demo@binmalikfitness.test', '/manage/manager'],
            ['admin.demo@binmalikfitness.test', '/manage/admin'],
        ];

        foreach ($pages as [$email, $uri]) {
            $html = $this->actingAs($this->user($email))->get($uri)->assertOk()->getContent();

            preg_match('/data-tabs="([^"]*)"/', $html, $match);
            $tabs = json_decode(html_entity_decode($match[1] ?? '[]'), true);
            $this->assertNotEmpty($tabs, "No tabs on {$uri}");

            foreach ($tabs as $tab) {
                $this->assertStringContainsString('data-tab-panel="'.$tab.'"', $html, "Tab {$tab} on {$uri} has no panel");
                $this->assertStringContainsString('id="tab-btn-'.$tab.'"', $html);
            }
        }
    }

    public function test_security_prune_command_runs(): void
    {
        $this->artisan('security:prune')->assertSuccessful();
    }
}
