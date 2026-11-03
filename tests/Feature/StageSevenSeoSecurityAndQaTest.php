<?php

namespace Tests\Feature;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\Cart;
use App\Models\CmsPage;
use App\Models\CoachingIntake;
use App\Models\Order;
use App\Models\PrivacyRequest;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SeoMetadata;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StageSevenSeoSecurityAndQaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sitemap_xml_and_robots_txt_expose_only_indexable_public_routes(): void
    {
        $robotsResponse = $this->get(route('seo.robots'));
        $robotsResponse->assertOk();
        $robotsResponse->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $robotsTxt = $robotsResponse->getContent();

        $this->assertStringContainsString('Disallow: /manage', $robotsTxt);
        $this->assertStringContainsString('Disallow: /account', $robotsTxt);
        $this->assertStringContainsString('Disallow: /cart', $robotsTxt);
        $this->assertStringContainsString('Disallow: /orders', $robotsTxt);
        $this->assertStringContainsString('Disallow: /private-files', $robotsTxt);
        $this->assertStringContainsString('Sitemap: '.route('seo.sitemap'), $robotsTxt);

        $sitemapResponse = $this->get(route('seo.sitemap'));
        $sitemapResponse->assertOk();
        $sitemapResponse->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = $sitemapResponse->getContent();

        // Must be valid parseable XML
        $parsedXml = simplexml_load_string((string) $xml);
        $this->assertNotFalse($parsedXml, 'Sitemap XML must be well-formed.');

        // Indexable public URLs must be present
        $this->assertStringContainsString(route('home'), $xml);
        $this->assertStringContainsString(route('coaching.index'), $xml);
        $this->assertStringContainsString(route('shop.index'), $xml);
        $this->assertStringContainsString(route('gym.index'), $xml);
        $this->assertStringContainsString(route('products.show', 'demo-whey-protein-isolate-5lb'), $xml);
        $this->assertStringContainsString(route('coaching.show', '12-week-complete-recomposition'), $xml);
        $this->assertStringContainsString(route('diet-plans.show', 'pakistani-fat-loss-blueprint-demo'), $xml);
        $this->assertStringContainsString(route('cms.pages.show', 'terms-and-conditions'), $xml);
        $this->assertStringContainsString(route('cms.pages.show', 'privacy-and-health-data-policy'), $xml);

        // Private, transactional, and staff routes must NEVER appear in sitemap.xml
        $this->assertStringNotContainsString('/manage', $xml);
        $this->assertStringNotContainsString('/account', $xml);
        $this->assertStringNotContainsString('/cart', $xml);
        $this->assertStringNotContainsString('/orders', $xml);
        $this->assertStringNotContainsString('/login', $xml);
    }

    public function test_published_cms_pages_render_publicly_and_draft_pages_return_404(): void
    {
        $termsResponse = $this->get(route('cms.pages.show', 'terms-and-conditions'));
        $termsResponse->assertOk();
        $termsResponse->assertSee('Terms &amp; Conditions', false);
        $termsResponse->assertSee('application/ld+json', false);

        $privacyResponse = $this->get(route('cms.pages.show', 'privacy-and-health-data-policy'));
        $privacyResponse->assertOk();
        $privacyResponse->assertSee('Privacy &amp; Confidential Health Data Policy [DEMO]', false);

        // Create a draft CMS page and verify it is not publicly accessible
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $draft = CmsPage::create([
            'slug' => 'internal-draft-policy',
            'title' => 'Internal Draft Policy',
            'section' => 'policy',
            'body' => 'Confidential draft body.',
            'status' => 'draft',
            'updated_by' => $admin->id,
        ]);

        $this->get(route('cms.pages.show', $draft->slug))->assertNotFound();
    }

    public function test_json_ld_structured_data_is_valid_and_never_fabricates_product_ratings(): void
    {
        // 1. Homepage JSON-LD (Organization, HealthClub, FAQPage)
        $homeHtml = $this->get(route('home'))->assertOk()->getContent();
        $homeSchemas = $this->extractJsonLdBlocks($homeHtml);
        $this->assertGreaterThanOrEqual(2, count($homeSchemas));
        $this->assertSame('Organization', $homeSchemas[0]['@graph'][0]['@type']);
        $this->assertSame('HealthClub', $homeSchemas[0]['@graph'][1]['@type']);
        $this->assertSame('FAQPage', $homeSchemas[1]['@type']);
        $this->assertNotEmpty($homeSchemas[1]['mainEntity']);

        // 2. Unreviewed product strictly omits AggregateRating and Review (never fabricates ratings)
        $wheyProduct = Product::where('slug', 'demo-whey-protein-isolate-5lb')->firstOrFail();
        ProductReview::where('product_id', $wheyProduct->id)->delete();

        $unreviewedHtml = $this->get(route('products.show', $wheyProduct->slug))->assertOk()->getContent();
        $unreviewedSchemas = $this->extractJsonLdBlocks($unreviewedHtml);
        $this->assertSame('Product', $unreviewedSchemas[0]['@type']);
        $this->assertSame('PKR', $unreviewedSchemas[0]['offers']['priceCurrency']);
        $this->assertSame('24500.00', $unreviewedSchemas[0]['offers']['price']);
        $this->assertArrayNotHasKey('aggregateRating', $unreviewedSchemas[0]);
        $this->assertArrayNotHasKey('review', $unreviewedSchemas[0]);
        $this->assertSame('BreadcrumbList', $unreviewedSchemas[1]['@type']);

        // 3. Once a verified-purchase review is approved, Product JSON-LD includes accurate AggregateRating
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $verifiedOrder = Order::where('order_number', 'BMF-DEMO-1001')->firstOrFail();
        ProductReview::create([
            'product_id' => $wheyProduct->id,
            'user_id' => $customer->id,
            'order_id' => $verifiedOrder->id,
            'rating' => 5,
            'title' => 'Clean mixability and batch-verified tub',
            'body' => 'Verified lot number on arrival in Lahore. Mixes cleanly with cold water post-training.',
            'is_verified_purchase' => true,
            'status' => 'approved',
        ]);

        $reviewedHtml = $this->get(route('products.show', $wheyProduct->slug))->assertOk()->getContent();
        $reviewedSchemas = $this->extractJsonLdBlocks($reviewedHtml);
        $this->assertSame('Product', $reviewedSchemas[0]['@type']);
        $this->assertArrayHasKey('aggregateRating', $reviewedSchemas[0]);
        $this->assertSame(5.0, (float) $reviewedSchemas[0]['aggregateRating']['ratingValue']);
        $this->assertSame(1, $reviewedSchemas[0]['aggregateRating']['reviewCount']);
        $this->assertArrayHasKey('review', $reviewedSchemas[0]);

        // 4. Coaching Package & Diet Plan JSON-LD schemas
        $coachingHtml = $this->get(route('coaching.show', '12-week-complete-recomposition'))->assertOk()->getContent();
        $coachingSchemas = $this->extractJsonLdBlocks($coachingHtml);
        $this->assertSame('Service', $coachingSchemas[0]['@type']);
        $this->assertSame('PKR', $coachingSchemas[0]['offers']['priceCurrency']);

        $dietHtml = $this->get(route('diet-plans.show', 'pakistani-fat-loss-blueprint-demo'))->assertOk()->getContent();
        $dietSchemas = $this->extractJsonLdBlocks($dietHtml);
        $this->assertSame('Course', $dietSchemas[0]['@type']);
        $this->assertSame('PKR', $dietSchemas[0]['offers']['priceCurrency']);
    }

    public function test_admin_seo_metadata_override_updates_head_tags_and_noindex_excludes_from_sitemap(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $product = Product::where('slug', 'demo-whey-protein-isolate-5lb')->firstOrFail();

        $this->actingAs($admin)->post(route('manage.seo-metadata.store'), [
            'seoable_type' => Product::class,
            'seoable_id' => $product->id,
            'meta_title' => 'Custom Isolate Whey Meta Title | BIN MALIK FITNESS',
            'meta_description' => 'Custom SEO meta description for Pakistani whey isolate.',
            'canonical_url' => 'https://binmalikfitness.com/products/demo-whey-protein-isolate-5lb',
            'robots' => 'noindex,nofollow',
        ])->assertRedirect();

        $productResponse = $this->get(route('products.show', $product->slug));
        $productResponse->assertOk();
        $productResponse->assertSee('<title>Custom Isolate Whey Meta Title | BIN MALIK FITNESS</title>', false);
        $productResponse->assertSee('content="Custom SEO meta description for Pakistani whey isolate."', false);
        $productResponse->assertSee('content="noindex,nofollow"', false);

        // Because robots is now noindex,nofollow, sitemap.xml must exclude this product URL
        $sitemapXml = $this->get(route('seo.sitemap'))->getContent();
        $this->assertStringNotContainsString(route('products.show', $product->slug), (string) $sitemapXml);

        // Restore index,follow
        SeoMetadata::where('seoable_type', Product::class)
            ->where('seoable_id', $product->id)
            ->update(['robots' => 'index,follow']);
    }

    public function test_security_headers_and_x_robots_tag_are_enforced_across_public_and_private_routes(): void
    {
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertOk();
        $homeResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $homeResponse->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $homeResponse->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty($homeResponse->headers->get('Content-Security-Policy'));
        $this->assertNull($homeResponse->headers->get('X-Robots-Tag'));

        // Transactional, Auth, Account, and Staff routes must emit X-Robots-Tag: noindex, nofollow
        $this->get(route('login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('cart.index'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $this->actingAs($customer)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('manage.admin'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_data_retention_token_pruning_and_customer_pii_anonymisation_preserve_financial_ledgers(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();

        // 1. Seed expired password reset token and stale guest cart
        DB::table('password_reset_tokens')->insert([
            'email' => 'expired@example.com',
            'token' => 'expired-token-hash',
            'created_at' => now()->subMinutes(120),
        ]);

        $staleCart = Cart::create([
            'session_id' => 'stale-guest-session',
            'currency' => 'PKR',
        ]);
        Cart::whereKey($staleCart->id)->update(['updated_at' => now()->subDays(45)]);

        // Manager cannot prune security tokens
        $this->actingAs($manager)
            ->post(route('manage.security.prune-tokens'))
            ->assertForbidden();

        // Admin prunes expired security tokens & stale guest carts
        $this->actingAs($admin)
            ->post(route('manage.security.prune-tokens'))
            ->assertRedirect();

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'expired@example.com']);
        $this->assertDatabaseMissing('carts', ['id' => $staleCart->id]);

        // 2. Test Account Deletion PII & Confidential Health Anonymisation while preserving financial order totals
        $customerToDelete = User::create([
            'name' => 'bilal.delete@example.com',
            'email' => 'bilal.delete@example.com',
            'phone' => '+923009998877',
            'password' => 'ChangeMe!Demo2026',
            'status' => 'active',
        ]);
        $customerToDelete->assignRole(RolePermissionMatrix::ROLE_CUSTOMER);

        $intake = CoachingIntake::create([
            'user_id' => $customerToDelete->id,
            'fitness_goal' => 'fat_loss',
            'age' => 31,
            'height_cm' => 176,
            'weight_kg' => 84,
            'activity_level' => 'moderate',
            'training_experience' => 'intermediate',
            'food_preferences' => 'Desi home meals',
            'allergies' => 'Peanuts',
            'equipment_availability' => 'Full Gym',
            'preferred_schedule' => '4 days/week',
            'health_consent_granted' => true,
            'health_notes_encrypted' => 'Confidential medical history to be scrubbed upon deletion.',
            'review_status' => CoachingIntake::STATUS_CLEARED,
        ]);

        $verifiedOrder = Order::create([
            'order_number' => 'BMF-ANON-9001',
            'business_line' => 'ecommerce',
            'access_token' => 'anon-order-token-9001',
            'user_id' => $customerToDelete->id,
            'customer_name' => 'Bilal Delete',
            'customer_email' => 'bilal.delete@example.com',
            'customer_phone' => '+923009998877',
            'shipping_address_snapshot' => ['line1' => 'House 10, Lahore'],
            'shipping_method' => 'standard',
            'payment_method' => 'bank_transfer',
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_VERIFIED,
            'currency' => 'PKR',
            'subtotal_paisa' => 1850000,
            'discount_paisa' => 0,
            'shipping_paisa' => 0,
            'tax_paisa' => 0,
            'total_paisa' => 1850000,
            'refunded_paisa' => 0,
            'placed_at' => now()->subDays(5),
        ]);

        $deletionRequest = PrivacyRequest::create([
            'user_id' => $customerToDelete->id,
            'request_type' => PrivacyRequest::TYPE_ACCOUNT_DELETION_ENQUIRY,
            'status' => PrivacyRequest::STATUS_PENDING,
            'customer_notes' => 'Please delete my personal profile and health notes.',
        ]);

        // Manager cannot execute account PII anonymisation
        $this->actingAs($manager)
            ->post(route('manage.privacy-requests.anonymise', $deletionRequest))
            ->assertForbidden();

        // Admin executes account PII anonymisation
        $this->actingAs($admin)
            ->post(route('manage.privacy-requests.anonymise', $deletionRequest))
            ->assertRedirect();

        $anonymisedUser = User::withTrashed()->findOrFail($customerToDelete->id);
        $this->assertTrue($anonymisedUser->trashed());
        $this->assertSame('deactivated', $anonymisedUser->status);
        $this->assertStringStartsWith('Anonymised Customer #', $anonymisedUser->name);
        $this->assertNull($anonymisedUser->phone);

        // Confidential health notes must be scrubbed
        $intake->refresh();
        $this->assertNull($intake->health_notes_encrypted);
        $this->assertSame('[REDACTED PER APPROVED PRIVACY REQUEST]', $intake->allergies);

        // Order PII must be scrubbed while financial totals (1,850,000 paisa) remain strictly intact
        $verifiedOrder->refresh();
        $this->assertSame($anonymisedUser->name, $verifiedOrder->customer_name);
        $this->assertSame('REDACTED', $verifiedOrder->customer_phone);
        $this->assertSame(1850000, (int) $verifiedOrder->total_paisa);
        $this->assertSame(Order::PAYMENT_VERIFIED, $verifiedOrder->payment_status);

        $deletionRequest->refresh();
        $this->assertSame(PrivacyRequest::STATUS_COMPLETED, $deletionRequest->status);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractJsonLdBlocks(string $html): array
    {
        preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches);
        $blocks = [];
        foreach ($matches[1] ?? [] as $rawJson) {
            $blocks[] = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
        }

        return $blocks;
    }
}
