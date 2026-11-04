<?php

namespace Tests\Feature;

use App\Domain\Platform\AnalyticsAndReportingService;
use App\Models\ConsentRecord;
use App\Models\Order;
use App\Models\PrivacyRequest;
use App\Models\Product;
use App\Models\RoleNotification;
use App\Models\SupportTicket;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageSixDashboardsAndOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_reconciled_analytics_excludes_unpaid_and_pending_proof_orders_and_reconciles_refunds(): void
    {
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $analytics = app(AnalyticsAndReportingService::class);

        $baseline = $analytics->getReconciledFinancialSummary(now()->subDays(90), now());

        // Create an unverified bank transfer order (proof_submitted) for PKR 50,000
        Order::create([
            'order_number' => 'BMF-UNVERIFIED-TEST',
            'access_token' => 'tok-unverified-test',
            'user_id' => $customer->id,
            'business_line' => 'shop',
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'payment_method' => 'bank_transfer',
            'payment_status' => Order::PAYMENT_PROOF_SUBMITTED,
            'currency' => 'PKR',
            'subtotal_paisa' => Money::fromRupees(50000),
            'total_paisa' => Money::fromRupees(50000),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0300-1112223',
            'shipping_address_snapshot' => ['city' => 'Lahore'],
            'placed_at' => now(),
        ]);

        // Reconciled verified revenue MUST NOT increase from an unverified order
        $afterUnverified = $analytics->getReconciledFinancialSummary(now()->subDays(90), now());
        $this->assertSame($baseline['net_reconciled_paisa'], $afterUnverified['net_reconciled_paisa']);
        $this->assertSame(
            $baseline['unverified_pipeline_paisa'] + Money::fromRupees(50000),
            $afterUnverified['unverified_pipeline_paisa']
        );

        // Create a verified order for PKR 20,000 with PKR 5,000 refunded (net PKR 15,000)
        Order::create([
            'order_number' => 'BMF-VERIFIED-PARTIAL-REFUND',
            'access_token' => 'tok-verified-partial',
            'user_id' => $customer->id,
            'business_line' => 'diet',
            'status' => Order::STATUS_PROCESSING,
            'payment_method' => 'bank_transfer',
            'payment_status' => Order::PAYMENT_PARTIALLY_REFUNDED,
            'currency' => 'PKR',
            'subtotal_paisa' => Money::fromRupees(20000),
            'total_paisa' => Money::fromRupees(20000),
            'refunded_paisa' => Money::fromRupees(5000),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0300-1112223',
            'shipping_address_snapshot' => ['city' => 'Lahore'],
            'placed_at' => now(),
            'confirmed_at' => now(),
        ]);

        $afterVerified = $analytics->getReconciledFinancialSummary(now()->subDays(90), now());
        $this->assertSame(
            $baseline['net_reconciled_paisa'] + Money::fromRupees(15000),
            $afterVerified['net_reconciled_paisa']
        );
        $this->assertSame(
            $baseline['by_business_line']['diet']['net_paisa'] + Money::fromRupees(15000),
            $afterVerified['by_business_line']['diet']['net_paisa']
        );
    }

    public function test_admin_can_export_reconciled_orders_csv_while_manager_and_customer_are_forbidden(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();

        $this->actingAs($customer)
            ->get(route('manage.reports.orders-export'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('manage.reports.orders-export'))
            ->assertForbidden();

        $response = $this->actingAs($admin)
            ->get(route('manage.reports.orders-export', [
                'date_from' => now()->subDays(90)->toDateString(),
                'date_to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $csvContent = $response->getContent();
        $this->assertStringContainsString('Net Reconciled Revenue (PKR)', (string) $csvContent);
        $this->assertStringContainsString('BMF-DEMO-1001', (string) $csvContent);
        // Unverified bank transfer order BMF-DEMO-1002 must NOT appear in verified revenue CSV export
        $this->assertStringNotContainsString('BMF-DEMO-1002', (string) $csvContent);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'reports.verified_orders.csv_exported',
        ]);
    }

    public function test_support_ticket_lifecycle_internal_note_isolation_and_cross_account_authorization(): void
    {
        $customer1 = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $customer2 = User::factory()->create([
            'name' => 'Second Demo Customer',
            'email' => 'customer2.stage6@binmalikfitness.test',
            'status' => 'active',
        ]);
        $customer2->assignRole('customer');
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $coach = User::where('email', 'coach.demo@binmalikfitness.test')->firstOrFail();

        // 1. Customer 1 opens a new support ticket
        $this->actingAs($customer1)
            ->post(route('account.support-tickets.store'), [
                'category' => 'order',
                'priority' => 'high',
                'subject' => 'Courier Delivery Time Window Request',
                'message' => 'Please ask courier to call before arriving at DHA Phase 5.',
            ])
            ->assertRedirect();

        $ticket = SupportTicket::where('subject', 'Courier Delivery Time Window Request')->firstOrFail();
        $this->assertSame($customer1->id, $ticket->user_id);
        $this->assertSame(SupportTicket::STATUS_OPEN, $ticket->status);

        // 2. Manager posts an internal staff note AND a public reply
        $this->actingAs($manager)
            ->post(route('manage.support-tickets.reply', $ticket), [
                'message' => 'CONFIDENTIAL STAFF NOTE: Verified courier dispatch manifest #882.',
                'is_internal_note' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($manager)
            ->post(route('manage.support-tickets.reply', $ticket), [
                'message' => 'We have instructed the courier to call your mobile 30 minutes prior to delivery.',
                'is_internal_note' => '0',
            ])
            ->assertRedirect();

        $this->assertSame(SupportTicket::STATUS_AWAITING_CUSTOMER, $ticket->fresh()->status);

        // 3. Customer 1 views /account — sees public reply, NEVER sees internal staff note
        $portalResponse = $this->actingAs($customer1)->get(route('account.dashboard'));
        $portalResponse->assertOk();
        $portalResponse->assertSee('We have instructed the courier to call your mobile 30 minutes prior to delivery.');
        $portalResponse->assertDontSee('CONFIDENTIAL STAFF NOTE: Verified courier dispatch manifest #882.');

        // 4. Customer 2 cannot reply to Customer 1's support ticket
        $this->actingAs($customer2)
            ->post(route('account.support-tickets.reply', $ticket), [
                'message' => 'Unauthorized cross-account reply attempt.',
            ])
            ->assertForbidden();

        // 5. Customer 2 opens a ticket; Coach (not assigned to Customer 2) cannot reply to Customer 2's ticket
        $this->actingAs($customer2)
            ->post(route('account.support-tickets.store'), [
                'category' => 'general',
                'priority' => 'normal',
                'subject' => 'Unassigned Customer Ticket',
                'message' => 'Testing coach assignment boundary on support tickets.',
            ]);
        $unassignedTicket = SupportTicket::where('subject', 'Unassigned Customer Ticket')->firstOrFail();

        $this->actingAs($coach)
            ->post(route('manage.support-tickets.reply', $unassignedTicket), [
                'message' => 'Coach attempting to reply to unassigned client ticket.',
            ])
            ->assertForbidden();
    }

    public function test_admin_cms_media_seo_notifications_and_consent_backed_testimonials_governance(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();

        // Manager is blocked from Admin-only CMS, Media, Testimonials, SEO & Notification Templates
        $this->actingAs($manager)
            ->post(route('manage.cms-pages.store'), [
                'title' => 'Unauthorized Page',
                'section' => 'policy',
                'body' => 'Body',
                'status' => 'published',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('manage.seo-metadata.store'), [
                'seoable_type' => Product::class,
                'seoable_id' => 1,
                'meta_title' => 'Unauthorized SEO',
                'meta_description' => 'Desc',
                'robots' => 'index,follow',
            ])
            ->assertForbidden();

        // Admin saves CMS Page, Media Asset, SEO Metadata & Notification Template
        $this->actingAs($admin)
            ->post(route('manage.cms-pages.store'), [
                'title' => 'Authenticity & Batch Verification Guarantee',
                'section' => 'policy',
                'excerpt' => 'Every tub includes importer invoice & batch verification.',
                'body' => 'Detailed authenticity verification policy.',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'authenticity-batch-verification-guarantee',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->post(route('manage.media-assets.store'), [
                'title' => 'Barbell Deadlift Platform Photo',
                'collection' => 'gym',
                'path' => '/images/demo/gym-interior.png',
                'alt_text' => 'Competition deadlift platform at BIN MALIK FITNESS',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('media_assets', [
            'title' => 'Barbell Deadlift Platform Photo',
            'collection' => 'gym',
        ]);

        $product = Product::firstOrFail();
        $this->actingAs($admin)
            ->post(route('manage.seo-metadata.store'), [
                'seoable_type' => Product::class,
                'seoable_id' => $product->id,
                'meta_title' => 'Buy Authentic Whey Isolate in Pakistan | BIN MALIK FITNESS',
                'meta_description' => 'Verified lot numbers, DRAP compliance documentation, and PKR delivery.',
                'canonical_url' => 'https://binmalikfitness.com/products/'.$product->slug,
                'robots' => 'index,follow',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('seo_metadata', [
            'seoable_type' => Product::class,
            'seoable_id' => $product->id,
            'meta_title' => 'Buy Authentic Whey Isolate in Pakistan | BIN MALIK FITNESS',
        ]);

        $this->actingAs($admin)
            ->post(route('manage.notification-templates.store'), [
                'key' => 'order.dispatched_courier',
                'name' => 'Courier Tracking Dispatched',
                'channel' => 'database',
                'subject' => 'Order {{order_number}} Dispatched',
                'body_template' => 'Your order {{order_number}} has shipped via {{courier_name}} ({{tracking_number}}).',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_templates', [
            'key' => 'order.dispatched_courier',
        ]);

        // Publishing a testimonial WITHOUT a ConsentRecord must fail validation
        $this->actingAs($admin)
            ->post(route('manage.testimonials.store'), [
                'author_label' => 'Unconsented Client Quote',
                'program_context' => '12-Week Coaching',
                'quote' => 'Should not be published without consent record.',
                'rating' => 5,
                'consent_record_id' => null,
                'status' => 'published',
            ])
            ->assertSessionHasErrors('consent_record_id');

        // Publishing WITH an active ConsentRecord succeeds
        $consent = ConsentRecord::where('user_id', $customer->id)
            ->whereNull('withdrawn_at')
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('manage.testimonials.store'), [
                'author_label' => 'Consented Coaching Client #208',
                'program_context' => '12-Week Transformation',
                'quote' => 'Verified strength and body-composition progress with consented display.',
                'rating' => 5,
                'consent_record_id' => $consent->id,
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('testimonials', [
            'author_label' => 'Consented Coaching Client #208',
            'status' => 'published',
            'consent_record_id' => $consent->id,
        ]);
    }

    public function test_customer_privacy_consent_withdrawal_automatically_unpublishes_linked_testimonial(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();

        $testimonial = Testimonial::where('status', 'published')->whereNotNull('consent_record_id')->firstOrFail();
        $consentId = $testimonial->consent_record_id;

        // Customer withdraws consent via Privacy Request
        $this->actingAs($customer)
            ->post(route('account.privacy-requests.store'), [
                'request_type' => PrivacyRequest::TYPE_CONSENT_WITHDRAWAL,
                'consent_record_id' => $consentId,
                'customer_notes' => 'Please withdraw my public testimonial display consent.',
            ])
            ->assertRedirect();

        $this->assertNotNull(ConsentRecord::findOrFail($consentId)->withdrawn_at);
        // Linked testimonial must automatically revert to draft when consent is withdrawn
        $this->assertSame('draft', $testimonial->fresh()->status);

        $privacyRequest = PrivacyRequest::where('user_id', $customer->id)
            ->where('request_type', PrivacyRequest::TYPE_CONSENT_WITHDRAWAL)
            ->firstOrFail();

        // Admin resolves the privacy request
        $this->actingAs($admin)
            ->put(route('manage.privacy-requests.resolve', $privacyRequest), [
                'status' => PrivacyRequest::STATUS_COMPLETED,
                'resolution_notes' => 'Consent withdrawn and public testimonial unpublished immediately.',
            ])
            ->assertRedirect();

        $this->assertSame(PrivacyRequest::STATUS_COMPLETED, $privacyRequest->fresh()->status);

        // Customer can mark their role notification as read
        $notification = RoleNotification::where('user_id', $customer->id)->whereNull('read_at')->firstOrFail();
        $this->actingAs($customer)
            ->post(route('account.notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_all_three_complete_stage_six_role_dashboards_render_cleanly(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('manage.admin'))
            ->assertOk()
            ->assertSee('Verified Revenue Analytics')
            ->assertSee('Support Tickets, Internal Staff Notes')
            ->assertSee('CMS Pages & Media Library')
            ->assertSee('Operational Health & Security Audit Logs');

        $this->actingAs($manager)
            ->get(route('manage.manager'))
            ->assertOk()
            ->assertSee('Assigned & Operational Support Tickets')
            ->assertSee('Operational KPIs & Approved Promotions');

        $this->actingAs($customer)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('My Support Tickets')
            ->assertSee('My Recorded Consents & Data Rights');
    }
}
