<?php

namespace Tests\Feature;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Platform\AuditLogger;
use App\Models\CoachingPackage;
use App\Models\DietPlan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_mysql_schema_and_demo_seeder_populate_all_four_business_lines(): void
    {
        $this->assertSame(12, ProductCategory::count());
        $this->assertGreaterThanOrEqual(5, Product::count());
        $this->assertSame(3, CoachingPackage::count());
        $this->assertGreaterThanOrEqual(7, DietPlan::count());

        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $coach = User::where('email', 'coach.demo@binmalikfitness.test')->firstOrFail();
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();

        $this->assertTrue($admin->hasRole(RolePermissionMatrix::ROLE_ADMIN));
        $this->assertTrue($manager->hasRole(RolePermissionMatrix::ROLE_MANAGER));
        $this->assertTrue($coach->hasRole(RolePermissionMatrix::ROLE_COACH));
        $this->assertFalse($coach->hasRole(RolePermissionMatrix::ROLE_MANAGER));
        $this->assertTrue($customer->hasRole(RolePermissionMatrix::ROLE_CUSTOMER));

        $this->assertTrue($admin->can('roles.manage'));
        $this->assertFalse($manager->can('roles.manage'));
        $this->assertFalse($manager->can('integrations.manage'));
        $this->assertFalse($manager->can('refunds.approve'));
        $this->assertTrue($manager->can('orders.fulfil'));
    }

    public function test_demo_seeder_refuses_to_run_in_production_environment(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DemoCatalogSeeder must never run in production.');

        app(DemoCatalogSeeder::class)->run();
    }

    public function test_product_variant_hides_staff_only_cost_price_on_serialization(): void
    {
        $product = Product::with('defaultVariant')->firstOrFail();
        $serialized = $product->defaultVariant->toArray();

        $this->assertArrayNotHasKey('cost_price', $serialized);
        $this->assertNotNull($product->defaultVariant->cost_price);
    }

    public function test_audit_logger_records_business_critical_events(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $product = Product::firstOrFail();

        $log = app(AuditLogger::class)->record(
            action: 'catalog.product.inspected',
            subject: $product,
            oldValues: ['status' => 'draft'],
            newValues: ['status' => 'active'],
            actor: $admin,
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'actor_id' => $admin->id,
            'action' => 'catalog.product.inspected',
            'subject_id' => $product->id,
        ]);
    }

    public function test_homepage_renders_all_required_sections_and_database_records(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('BIN MALIK FITNESS')
            ->assertSee('Start Online Coaching')
            ->assertSee('Shop Supplements')
            ->assertSee('Choose Your Discipline')
            ->assertSee('Authentic Transformation Results')
            ->assertSee('12-Week Complete Recomposition [DEMO]')
            ->assertSee('High-Protein Pakistani Fat-Loss Blueprint [DEMO]')
            ->assertSee('[DEMO] Whey Protein Isolate')
            ->assertSee('Free Training Video Previews')
            ->assertSee('Verified Client Testimonials')
            ->assertSee('Frequently Asked Questions')
            ->assertSee('Gym Location, Memberships & Operating Hours')
            ->assertSee('WhatsApp Support');
    }

    public function test_all_public_and_authenticated_role_routes_work_end_to_end(): void
    {
        $publicRoutes = [
            '/shop',
            '/shop?category=whey-protein',
            '/products/demo-whey-protein-isolate-5lb',
            '/cart',
            '/coaching',
            '/coaching/12-week-complete-recomposition',
            '/diet-plans/pakistani-fat-loss-blueprint-demo',
            '/login',
            '/forgot-password',
            '/design-system',
        ];

        foreach ($publicRoutes as $uri) {
            $this->get($uri)->assertOk();
        }

        // Dashboards require authentication
        $this->get('/account')->assertRedirect('/login');
        $this->get('/manage/admin')->assertRedirect('/login');
        $this->get('/manage/manager')->assertRedirect('/login');

        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();

        $this->actingAs($admin)->get('/manage/admin')->assertOk();
        $this->actingAs($manager)->get('/manage/manager')->assertOk();
        $this->actingAs($customer)->get('/account')->assertOk();
    }
}
