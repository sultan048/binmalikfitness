<?php

namespace Database\Seeders;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\Address;
use App\Models\Brand;
use App\Models\CmsPage;
use App\Models\CoachAssignment;
use App\Models\CoachingCheckIn;
use App\Models\CoachingConsultation;
use App\Models\CoachingIntake;
use App\Models\CoachingMessage;
use App\Models\CoachingPackage;
use App\Models\CoachingSubscription;
use App\Models\ConsentRecord;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\DietPlan;
use App\Models\DietPlanVersion;
use App\Models\Enquiry;
use App\Models\Entitlement;
use App\Models\Faq;
use App\Models\GymCheckIn;
use App\Models\GymClass;
use App\Models\GymClassBooking;
use App\Models\GymHoliday;
use App\Models\GymMembership;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\MediaAsset;
use App\Models\MembershipPlan;
use App\Models\NotificationTemplate;
use App\Models\OpeningHour;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PersonalisedPlanRequest;
use App\Models\PrivacyRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDocument;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\RoleNotification;
use App\Models\SeoMetadata;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Video;
use App\Models\WorkoutAssignment;
use App\Models\WorkoutProgram;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoCatalogSeeder must never run in production.');
        }

        // 1. Configurable platform settings (clearly marked pending owner confirmation)
        $settings = [
            ['group' => 'brand', 'key' => 'brand.name', 'value' => 'BIN MALIK FITNESS'],
            ['group' => 'brand', 'key' => 'brand.domain', 'value' => 'https://binmalikfitness.com'],
            ['group' => 'brand', 'key' => 'brand.currency', 'value' => 'PKR'],
            ['group' => 'gym', 'key' => 'gym.city', 'value' => 'City Pending Owner Confirmation (Pakistan)'],
            ['group' => 'gym', 'key' => 'gym.address', 'value' => 'Facility street address & sector pending owner confirmation'],
            ['group' => 'gym', 'key' => 'gym.phone', 'value' => '+92 300 0000000 (Demo Placeholder)'],
            ['group' => 'gym', 'key' => 'gym.whatsapp', 'value' => '923000000000'],
            ['group' => 'gym', 'key' => 'gym.email', 'value' => 'info@binmalikfitness.com'],
            ['group' => 'coach', 'key' => 'coach.head_name', 'value' => 'Head Coach Profile (Pending Owner Bio & Credentials)'],
            ['group' => 'payment', 'key' => 'payment.bank_name', 'value' => 'Meezan Bank / HBL (Demo Placeholder — Pending Owner Confirmation)'],
            ['group' => 'payment', 'key' => 'payment.account_title', 'value' => 'BIN MALIK FITNESS (Demo Account Title)'],
            ['group' => 'payment', 'key' => 'payment.iban', 'value' => 'PK00MEZN0000000000000000 (Demo IBAN)'],
        ];

        foreach ($settings as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                ['group' => $item['group'], 'value' => $item['value'], 'is_demo' => true]
            );
        }

        // 2. Demo accounts for local/testing role verification (blocked in production)
        $admin = User::updateOrCreate(
            ['email' => 'admin.demo@binmalikfitness.test'],
            [
                'name' => 'Demo Owner / Admin',
                'phone' => '0300-0000001',
                'password' => Hash::make('DemoOnly!NotForProd#1'),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_demo' => true,
            ]
        );
        $admin->syncRoles([RolePermissionMatrix::ROLE_ADMIN]);

        $manager = User::updateOrCreate(
            ['email' => 'manager.demo@binmalikfitness.test'],
            [
                'name' => 'Demo Operations Manager',
                'phone' => '0300-0000002',
                'password' => Hash::make('DemoOnly!NotForProd#2'),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_demo' => true,
            ]
        );
        $manager->syncRoles([RolePermissionMatrix::ROLE_MANAGER, RolePermissionMatrix::ROLE_COACH]);

        StaffProfile::updateOrCreate(
            ['user_id' => $manager->id],
            [
                'job_title' => 'Lead Strength & Nutrition Coach [DEMO PROFILE]',
                'headline' => 'Demonstration coach profile — replace with verified owner/head-coach biography and credentials before launch.',
                'bio' => 'This profile slot is reserved for the authentic BIN MALIK FITNESS head coach biography, training philosophy, competitive history, and verified coaching certifications.',
                'qualifications' => [
                    'Certification Slot #1 — Pending Owner Verification',
                    'Certification Slot #2 — Pending Sports Nutrition Credential',
                    'First Aid / CPR Status — Pending Confirmation',
                ],
                'specialties' => [
                    'Strength & Hypertrophy Programming',
                    'Body Recomposition & Macronutrient Structuring',
                    'Barbell Mechanics & Injury-Aware Progression',
                ],
                'is_public_coach' => true,
                'is_demo' => true,
            ]
        );

        $customer = User::updateOrCreate(
            ['email' => 'customer.demo@binmalikfitness.test'],
            [
                'name' => 'Demo Coaching Member',
                'phone' => '0300-0000003',
                'password' => Hash::make('DemoOnly!NotForProd#3'),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_demo' => true,
            ]
        );
        $customer->syncRoles([RolePermissionMatrix::ROLE_CUSTOMER]);

        CustomerProfile::updateOrCreate(
            ['user_id' => $customer->id],
            [
                'gender' => 'male',
                'height_cm' => 178,
                'weight_kg' => 84.50,
                'fitness_goal' => 'Body Recomposition & Strength',
                'activity_level' => 'Moderately Active (Desk job + 4x lifting)',
                'experience_level' => 'Intermediate (2+ years barbell training)',
                'dietary_preferences' => ['Halal South Asian', 'High Protein'],
                'equipment_access' => 'Full Commercial Barbell Gym',
            ]
        );

        Address::updateOrCreate(
            ['user_id' => $customer->id, 'label' => 'Primary Residence'],
            [
                'recipient_name' => 'Demo Coaching Member',
                'phone' => '0300-0000003',
                'line1' => 'House 14, Street 6, Sector Demo (Placeholder)',
                'area' => 'Model Town',
                'city' => 'Lahore',
                'province' => 'Punjab',
                'postal_code' => '54000',
                'is_default' => true,
            ]
        );

        $coachOnly = User::updateOrCreate(
            ['email' => 'coach.demo@binmalikfitness.test'],
            [
                'name' => 'Demo Assigned Coach (Staff)',
                'phone' => '0300-0000004',
                'password' => Hash::make('DemoOnly!NotForProd#4'),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_demo' => true,
            ]
        );
        $coachOnly->syncRoles([RolePermissionMatrix::ROLE_COACH]);

        StaffProfile::updateOrCreate(
            ['user_id' => $coachOnly->id],
            [
                'job_title' => 'Assigned Strength Coach [DEMO]',
                'headline' => 'Internal staff coach with client-scoped permissions only.',
                'bio' => 'Holds coach-specific permissions without full Manager operational powers.',
                'qualifications' => ['Strength & Conditioning Certification (Pending Verification)'],
                'specialties' => ['Barbell Technique', 'Weekly Check-In Review'],
                'is_public_coach' => false,
                'is_demo' => true,
            ]
        );

        $unassignedCustomer = User::updateOrCreate(
            ['email' => 'unassigned.customer@binmalikfitness.test'],
            [
                'name' => 'Demo Unassigned Customer',
                'phone' => '0300-0000005',
                'password' => Hash::make('DemoOnly!NotForProd#5'),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_demo' => true,
            ]
        );
        $unassignedCustomer->syncRoles([RolePermissionMatrix::ROLE_CUSTOMER]);

        CoachAssignment::updateOrCreate(
            ['coach_id' => $manager->id, 'client_id' => $customer->id, 'context' => 'coaching'],
            [
                'assigned_by' => $admin->id,
                'starts_at' => now()->subDays(7),
                'ends_at' => null,
                'notes' => 'Seeded demonstration coaching assignment.',
            ]
        );

        CoachAssignment::updateOrCreate(
            ['coach_id' => $coachOnly->id, 'client_id' => $customer->id, 'context' => 'diet'],
            [
                'assigned_by' => $admin->id,
                'starts_at' => now()->subDays(7),
                'ends_at' => null,
                'notes' => 'Seeded demonstration nutrition assignment.',
            ]
        );

        // 3. Required 12 Supplement Categories
        $categories = [
            ['name' => 'Whey Protein', 'slug' => 'whey-protein', 'summary' => 'Isolate, concentrate and blended whey protein powders.'],
            ['name' => 'Mass Gainers', 'slug' => 'mass-gainers', 'summary' => 'High-calorie carbohydrate and protein blends for size phases.'],
            ['name' => 'Creatine', 'slug' => 'creatine', 'summary' => 'Micronized creatine monohydrate for strength and power output.'],
            ['name' => 'Pre-Workout', 'slug' => 'pre-workout', 'summary' => 'Focus, pump and ergogenic pre-training formulas.'],
            ['name' => 'BCAA / EAA', 'slug' => 'bcaa-eaa', 'summary' => 'Essential and branched-chain amino acids for intra-workout support.'],
            ['name' => 'Vitamins and Minerals', 'slug' => 'vitamins-minerals', 'summary' => 'Daily micronutrient, omega-3 and electrolyte support.'],
            ['name' => 'Recovery Supplements', 'slug' => 'recovery-supplements', 'summary' => 'Post-training recovery, glutamine and sleep support formulas.'],
            ['name' => 'Weight Management Products', 'slug' => 'weight-management', 'summary' => 'Calorie-phase support and metabolic formulas.'],
            ['name' => 'Protein Bars and Nutrition', 'slug' => 'protein-bars-nutrition', 'summary' => 'On-the-go protein snacks and functional foods.'],
            ['name' => 'Gym Accessories', 'slug' => 'gym-accessories', 'summary' => 'Lifting straps, belts, shakers and gym bags.'],
            ['name' => 'Fitness Equipment', 'slug' => 'fitness-equipment', 'summary' => 'Home and commercial resistance bands, dumbbells and attachments.'],
            ['name' => 'Other Configurable Categories', 'slug' => 'other-supplements', 'summary' => 'Speciality stacks and seasonal releases.'],
        ];

        $categoryMap = [];
        foreach ($categories as $idx => $cat) {
            $categoryMap[$cat['slug']] = ProductCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'summary' => $cat['summary'],
                    'description' => $cat['summary'].' All listings require batch, expiry and importer documentation prior to public sale.',
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                    'is_demo' => true,
                ]
            );
        }

        // 4. Demo Brands (unbranded demonstration placeholders)
        $brandLab = Brand::updateOrCreate(
            ['slug' => 'demo-nutrition-labs'],
            [
                'name' => 'Demo Nutrition Labs (Placeholder Brand)',
                'country_of_origin' => 'Pending Importer Confirmation',
                'authorised_distributor_note' => 'Demonstration brand record — replace with verified authorised distributor brands.',
                'is_active' => true,
                'is_demo' => true,
            ]
        );

        $brandGear = Brand::updateOrCreate(
            ['slug' => 'bmf-athletic-gear-demo'],
            [
                'name' => 'BMF Athletic Gear (Demo)',
                'country_of_origin' => 'Pakistan',
                'authorised_distributor_note' => 'Demonstration accessory line.',
                'is_active' => true,
                'is_demo' => true,
            ]
        );

        // 5. Demo Products (clearly labeled demonstration catalogue entries)
        $products = [
            [
                'category_slug' => 'whey-protein',
                'brand_id' => $brandLab->id,
                'name' => '[DEMO] Whey Protein Isolate — 5 lb Sample Listing',
                'slug' => 'demo-whey-protein-isolate-5lb',
                'sku' => 'DEMO-WPI-5LB',
                'subtitle' => '25g protein per scoop · Demonstration catalog item (sample price)',
                'short_description' => 'Unbranded demonstration listing illustrating batch/lot tracking, nutrition panel, importer disclosure, and variant selection.',
                'description' => 'This product is a clearly labeled demonstration item used to verify catalog layout, nutrition facts rendering, batch/expiry disclosure, and PKR pricing calculations. Replace with authentic inventory and verified importer documentation before launch.',
                'ingredients' => ['Whey Protein Isolate (Milk)', 'Natural Cocoa Powder', 'Soy Lecithin', 'Sucralose (Sample Label)'],
                'nutrition_facts' => [
                    'serving_size' => '30 g (1 scoop)',
                    'servings_per_container' => 73,
                    'calories' => '120 kcal',
                    'protein' => '25 g',
                    'carbohydrates' => '2 g',
                    'fat' => '1 g',
                ],
                'usage_instructions' => 'Mix 1 scoop (30 g) with 200–250 ml cold water or milk post-training or as needed to meet daily protein targets.',
                'warnings' => 'Food supplement for healthy adults. Keep out of reach of children. Store in a cool, dry place.',
                'allergens' => 'Contains Milk and Soy lecithin.',
                'manufacturer' => 'Pending Manufacturer Confirmation',
                'importer' => 'Pending Authorised Pakistan Importer Details',
                'regulatory_status' => 'pending_verification',
                'authenticity_note' => 'Batch & importer invoice verification required before live activation.',
                'image' => '/images/demo/product-whey-800.webp',
                'variants' => [
                    ['sku' => 'DEMO-WPI-5LB-CHOC', 'size' => '5 lb (2.27 kg)', 'flavor' => 'Double Rich Chocolate', 'servings' => 73, 'price' => Money::fromRupees(24500), 'compare' => Money::fromRupees(26500), 'stock' => 14, 'default' => true],
                    ['sku' => 'DEMO-WPI-5LB-VAN', 'size' => '5 lb (2.27 kg)', 'flavor' => 'French Vanilla', 'servings' => 73, 'price' => Money::fromRupees(24500), 'compare' => null, 'stock' => 8, 'default' => false],
                    ['sku' => 'DEMO-WPI-2LB-CHOC', 'size' => '2 lb (908 g)', 'flavor' => 'Double Rich Chocolate', 'servings' => 29, 'price' => Money::fromRupees(11800), 'compare' => null, 'stock' => 19, 'default' => false],
                ],
            ],
            [
                'category_slug' => 'creatine',
                'brand_id' => $brandLab->id,
                'name' => '[DEMO] Micronized Creatine Monohydrate — 300 g',
                'slug' => 'demo-micronized-creatine-300g',
                'sku' => 'DEMO-CRE-300G',
                'subtitle' => '5g pure monohydrate per serving · 60 servings (Sample Price)',
                'short_description' => 'Unflavored 200-mesh micronized creatine monohydrate demonstration listing.',
                'description' => 'Demonstration creatine listing showing single-ingredient supplement disclosures, serving math, and stock thresholds.',
                'ingredients' => ['100% Creatine Monohydrate (Sample Label)'],
                'nutrition_facts' => [
                    'serving_size' => '5 g (1 scoop)',
                    'servings_per_container' => 60,
                    'creatine_monohydrate' => '5,000 mg',
                ],
                'usage_instructions' => 'Take 1 scoop (5 g) daily with 250 ml water or juice.',
                'warnings' => 'Maintain adequate daily hydration. Consult a physician if you have renal conditions.',
                'allergens' => 'None declared on sample label.',
                'manufacturer' => 'Pending Manufacturer Confirmation',
                'importer' => 'Pending Authorised Pakistan Importer Details',
                'regulatory_status' => 'pending_verification',
                'authenticity_note' => 'Lot & expiry tracked per shipment.',
                'image' => '/images/demo/product-creatine-800.webp',
                'variants' => [
                    ['sku' => 'DEMO-CRE-300G-UNF', 'size' => '300 g (60 Servings)', 'flavor' => 'Unflavored', 'servings' => 60, 'price' => Money::fromRupees(6800), 'compare' => Money::fromRupees(7500), 'stock' => 22, 'default' => true],
                ],
            ],
            [
                'category_slug' => 'pre-workout',
                'brand_id' => $brandLab->id,
                'name' => '[DEMO] High-Focus Pre-Workout Formula — 30 Servings',
                'slug' => 'demo-pre-workout-30srv',
                'sku' => 'DEMO-PRE-30',
                'subtitle' => 'Citrulline, Beta-Alanine & Caffeine disclosure example (Sample Price)',
                'short_description' => 'Demonstration pre-workout showing caffeine warning disclosures and label transparency.',
                'description' => 'Demonstration catalog item for pre-training formulas with explicit caffeine warning and serving guidance.',
                'ingredients' => ['L-Citrulline Malate', 'Beta-Alanine', 'Caffeine Anhydrous (200 mg/serving)', 'L-Tyrosine'],
                'nutrition_facts' => [
                    'serving_size' => '12 g (1 scoop)',
                    'servings_per_container' => 30,
                    'citrulline_malate' => '6,000 mg',
                    'beta_alanine' => '3,200 mg',
                    'caffeine_anhydrous' => '200 mg',
                ],
                'usage_instructions' => 'Mix 1 scoop in 250 ml cold water 20–30 minutes before training. Do not exceed 1 scoop in 24 hours.',
                'warnings' => 'High caffeine content (200 mg per serving). Not recommended for children, pregnant or nursing women, or caffeine-sensitive individuals.',
                'allergens' => 'Manufactured in a facility that processes milk and soy (sample disclosure).',
                'manufacturer' => 'Pending Manufacturer Confirmation',
                'importer' => 'Pending Authorised Pakistan Importer Details',
                'regulatory_status' => 'pending_verification',
                'authenticity_note' => 'Pending owner regulatory document upload.',
                'image' => '/images/demo/product-preworkout-800.webp',
                'variants' => [
                    ['sku' => 'DEMO-PRE-30-CIT', 'size' => '360 g (30 Servings)', 'flavor' => 'Blood Orange', 'servings' => 30, 'price' => Money::fromRupees(8900), 'compare' => null, 'stock' => 11, 'default' => true],
                ],
            ],
            [
                'category_slug' => 'gym-accessories',
                'brand_id' => $brandGear->id,
                'name' => '[DEMO] Matte Black Leak-Proof Training Shaker — 700 ml',
                'slug' => 'demo-matte-black-shaker-700ml',
                'sku' => 'DEMO-ACC-SHK700',
                'subtitle' => 'BPA-free polypropylene · Stainless mixing grid (Sample Price)',
                'short_description' => 'Heavy-duty 700 ml gym shaker bottle with measurement markings.',
                'description' => 'Demonstration gym accessory item for non-consumable catalog testing.',
                'ingredients' => null,
                'nutrition_facts' => null,
                'usage_instructions' => 'Wash with warm soapy water before first use. Top-rack dishwasher safe.',
                'warnings' => 'Do not use with carbonated or boiling liquids.',
                'allergens' => 'BPA & DEHP Free.',
                'manufacturer' => 'BMF Partner Manufacturing (Demo)',
                'importer' => 'Local Pakistan Supply (Demo)',
                'regulatory_status' => 'exempt_accessory',
                'authenticity_note' => 'Direct brand accessory.',
                'image' => '/images/demo/product-shaker-800.webp',
                'variants' => [
                    ['sku' => 'DEMO-ACC-SHK700-BLK', 'size' => '700 ml', 'flavor' => 'Matte Black', 'servings' => null, 'price' => Money::fromRupees(1850), 'compare' => null, 'stock' => 40, 'default' => true],
                ],
            ],
            [
                'category_slug' => 'gym-accessories',
                'brand_id' => $brandGear->id,
                'name' => '[DEMO] Heavy-Duty Cotton Barbell Lifting Straps (Pair)',
                'slug' => 'demo-heavy-duty-lifting-straps',
                'sku' => 'DEMO-ACC-STRP',
                'subtitle' => 'Reinforced box stitching · Neoprene wrist padding (Sample Price)',
                'short_description' => 'Built for heavy deadlifts, rows and shrugs without grip failure.',
                'description' => 'Demonstration lifting straps listing for accessory cross-sells and cart threshold testing.',
                'ingredients' => null,
                'nutrition_facts' => null,
                'usage_instructions' => 'Loop strap around wrist and wrap tail tightly around barbell knurling.',
                'warnings' => 'Inspect stitching regularly under maximal loads.',
                'allergens' => null,
                'manufacturer' => 'Sialkot Sports Manufacturing (Demo)',
                'importer' => 'Made in Pakistan (Demo)',
                'regulatory_status' => 'exempt_accessory',
                'authenticity_note' => 'Direct workshop QC.',
                'image' => '/images/demo/product-straps-800.webp',
                'variants' => [
                    ['sku' => 'DEMO-ACC-STRP-STD', 'size' => 'One Size (60 cm)', 'flavor' => 'Stealth Black', 'servings' => null, 'price' => Money::fromRupees(2200), 'compare' => Money::fromRupees(2600), 'stock' => 35, 'default' => true],
                ],
            ],
        ];

        foreach ($products as $pData) {
            $product = Product::updateOrCreate(
                ['slug' => $pData['slug']],
                [
                    'category_id' => $categoryMap[$pData['category_slug']]->id,
                    'brand_id' => $pData['brand_id'],
                    'name' => $pData['name'],
                    'sku' => $pData['sku'],
                    'subtitle' => $pData['subtitle'],
                    'short_description' => $pData['short_description'],
                    'description' => $pData['description'],
                    'ingredients' => $pData['ingredients'],
                    'nutrition_facts' => $pData['nutrition_facts'],
                    'usage_instructions' => $pData['usage_instructions'],
                    'warnings' => $pData['warnings'],
                    'allergens' => $pData['allergens'],
                    'manufacturer' => $pData['manufacturer'],
                    'importer' => $pData['importer'],
                    'batch_tracking_enabled' => $pData['regulatory_status'] !== 'exempt_accessory',
                    'regulatory_status' => $pData['regulatory_status'],
                    'authenticity_note' => $pData['authenticity_note'],
                    'status' => 'active',
                    'is_featured' => true,
                    'is_bestseller' => true,
                    'is_demo' => true,
                ]
            );

            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'is_primary' => true],
                [
                    'path' => $pData['image'],
                    'alt_text' => $pData['name'],
                    'sort_order' => 1,
                ]
            );

            ProductDocument::updateOrCreate(
                ['product_id' => $product->id, 'document_type' => 'importer_invoice'],
                [
                    'title' => 'Supplier & Batch Verification Record [DEMO]',
                    'reference_number' => 'DOC-'.$product->sku.'-2026',
                    'status' => $product->regulatory_status === 'exempt_accessory' ? 'verified' : 'pending_review',
                    'issued_at' => now()->subMonths(2)->toDateString(),
                    'expires_at' => now()->addMonths(14)->toDateString(),
                    'is_demo' => true,
                ]
            );

            foreach ($pData['variants'] as $vData) {
                $variant = ProductVariant::updateOrCreate(
                    ['sku' => $vData['sku']],
                    [
                        'product_id' => $product->id,
                        'size' => $vData['size'],
                        'flavor' => $vData['flavor'],
                        'servings' => $vData['servings'],
                        'price' => $vData['price'],
                        'compare_at_price' => $vData['compare'],
                        'cost_price' => (int) round($vData['price'] * 0.72),
                        'stock_on_hand' => $vData['stock'],
                        'stock_reserved' => 0,
                        'low_stock_threshold' => 5,
                        'weight_grams' => 1000,
                        'sample_lot_number' => 'DEMO-LOT-2026A',
                        'sample_expiry_date' => now()->addMonths(14)->toDateString(),
                        'is_default' => $vData['default'],
                        'is_active' => true,
                    ]
                );

                $batch = InventoryBatch::updateOrCreate(
                    ['product_variant_id' => $variant->id, 'batch_number' => 'DEMO-LOT-2026A'],
                    [
                        'manufactured_at' => now()->subMonths(4)->toDateString(),
                        'expires_at' => now()->addMonths(14)->toDateString(),
                        'quantity_received' => $vData['stock'],
                        'quantity_remaining' => $vData['stock'],
                        'unit_cost_paisa' => (int) round($vData['price'] * 0.72),
                        'supplier_reference' => 'DEMO-SUP-INV-2026',
                        'received_by' => $admin->id,
                        'is_demo' => true,
                    ]
                );

                if (! InventoryMovement::where('product_variant_id', $variant->id)->where('type', 'receive')->exists()) {
                    InventoryMovement::create([
                        'product_variant_id' => $variant->id,
                        'inventory_batch_id' => $batch->id,
                        'type' => 'receive',
                        'quantity_delta' => $vData['stock'],
                        'on_hand_after' => $vData['stock'],
                        'reserved_after' => 0,
                        'reference_type' => 'batch',
                        'reference_id' => $batch->id,
                        'reason' => 'Initial demonstration batch receipt',
                        'actor_id' => $admin->id,
                        'created_at' => now(),
                    ]);
                }
            }
        }

        // 6. Demo Coaching Packages (Primary homepage conversion objective)
        $coachingPackages = [
            [
                'name' => '8-Week Foundation Coaching [DEMO]',
                'slug' => '8-week-foundation-coaching',
                'tagline' => 'Structured training + Pakistani-household nutrition audit + weekly check-ins.',
                'duration_days' => 56,
                'price' => Money::fromRupees(28000),
                'compare_at_price' => null,
                'billing_note' => 'Sample one-time cycle price · Manual renewal after 8 weeks',
                'checkins_per_week' => 1,
                'is_featured' => false,
                'sort_order' => 1,
                'inclusions' => [
                    'Comprehensive initial intake & movement/equipment review',
                    'Periodized 8-week gym or home workout block',
                    'Calorie & protein targets using local South Asian food options',
                    'Weekly structured check-in with coach feedback within 24h',
                    'Access to exercise execution video library during active term',
                ],
                'who_is_it_for' => 'Lifters seeking structure, accountability, and clear progression without guesswork.',
            ],
            [
                'name' => '12-Week Complete Recomposition [DEMO]',
                'slug' => '12-week-complete-recomposition',
                'tagline' => 'Our flagship 1-to-1 online coaching protocol for fat loss and strength retention.',
                'duration_days' => 84,
                'price' => Money::fromRupees(39000),
                'compare_at_price' => Money::fromRupees(44000),
                'billing_note' => 'Sample 12-week package · Most selected online coaching tier',
                'checkins_per_week' => 2,
                'is_featured' => true,
                'sort_order' => 2,
                'inclusions' => [
                    '1-on-1 onboarding consultation call (video/audio)',
                    'Custom 3-phase training mesocycle tailored to your gym setup',
                    'Personalized meal structure with approved desi & continental swaps',
                    'Twice-weekly progress & biofeedback check-ins + form video reviews',
                    'Direct secure coach-client portal messaging & full video library entitlement',
                ],
                'who_is_it_for' => 'Busy professionals and committed trainees targeting measurable body recomposition over 90 days.',
            ],
            [
                'name' => '16-Week Strength & Physique Intensive [DEMO]',
                'slug' => '16-week-strength-physique-intensive',
                'tagline' => 'Extended high-touch preparation with bi-weekly program adjustments and form analysis.',
                'duration_days' => 112,
                'price' => Money::fromRupees(52000),
                'compare_at_price' => null,
                'billing_note' => 'Sample 16-week high-touch tier · Limited coach roster slots',
                'checkins_per_week' => 2,
                'is_featured' => false,
                'sort_order' => 3,
                'inclusions' => [
                    'Priority coach assignment & bi-weekly strategy consultations',
                    'Multi-phase strength & hypertrophy periodization',
                    'Dynamic nutrition adjustments across surplus/deficit phases',
                    'Detailed lift video breakdown (squat, bench, deadlift, overhead)',
                    'Supplement stack guidance based on verified lab/diet needs',
                ],
                'who_is_it_for' => 'Intermediate-to-advanced lifters wanting closely supervised strength and physique blocks.',
            ],
        ];

        foreach ($coachingPackages as $pkg) {
            CoachingPackage::updateOrCreate(
                ['slug' => $pkg['slug']],
                array_merge($pkg, [
                    'includes_custom_diet' => true,
                    'includes_custom_workout' => true,
                    'includes_video_library' => true,
                    'includes_consultation' => true,
                    'status' => 'active',
                    'is_demo' => true,
                ])
            );
        }

        // 7. Demo Diet Plans (Free preview, One-time paid, Personalised)
        $dietPlans = [
            [
                'title' => 'High-Protein Pakistani Fat-Loss Blueprint [DEMO]',
                'slug' => 'pakistani-fat-loss-blueprint-demo',
                'category' => 'Fat Loss / Weight Loss',
                'type' => 'paid',
                'duration_days' => 28,
                'target_calories' => '1,800 – 2,200 kcal (tiered)',
                'price' => Money::fromRupees(4500),
                'summary' => '4-week structured fat-loss nutrition guide built around everyday Pakistani kitchen staples (chicken karahi, daal, roti, eggs, yogurt).',
                'description' => 'Demonstration digital diet plan showing how structured meal schedules, gram/household portion measurements, approved food substitutions, and weekly grocery lists are delivered upon verified purchase.',
                'sample_day' => [
                    ['meal' => 'Meal 1 — Breakfast (08:30)', 'items' => '3 whole eggs + 2 egg whites omelette, 1 medium whole-wheat chapati (45g atta), black tea / coffee (no sugar)', 'macros' => '32g P · 28g C · 16g F · ~385 kcal'],
                    ['meal' => 'Meal 2 — Lunch (13:30)', 'items' => '180g raw-weight grilled chicken breast tikka, 150g cooked boiled white basmati rice, cucumber-mint raita (100g low-fat dahi)', 'macros' => '56g P · 45g C · 8g F · ~475 kcal'],
                    ['meal' => 'Meal 3 — Pre-Training (17:30)', 'items' => '1 medium banana + 1 scoop whey protein in water (or 200g boiled chana chaat swap)', 'macros' => '25g P · 30g C · 2g F · ~240 kcal'],
                    ['meal' => 'Meal 4 — Dinner (20:30)', 'items' => '180g lean beef/chicken qeema (measured oil: 1 tsp olive/desi ghee), 1 whole-wheat chapati, mixed kachumber salad, 1 cup cooked moong daal', 'macros' => '52g P · 48g C · 18g F · ~560 kcal'],
                ],
                'inclusions' => [
                    '3 calorie tiers (1,800 / 2,000 / 2,200 kcal)',
                    'Gram + household spoon/cup portion equivalents',
                    'Approved food substitution table (proteins, carbs, fats)',
                    'Weekly mandi/supermarket shopping list',
                ],
                'dietary_tags' => ['Halal', 'South Asian Kitchen', 'High Protein'],
                'disclaimer' => 'Educational nutrition framework for healthy adults. Not a medical prescription or clinical diet therapy.',
                'image_path' => '/images/demo/diet-meal-1200.webp',
                'is_featured' => true,
            ],
            [
                'title' => 'Lean Muscle Mass & Strength Nutrition Protocol [DEMO]',
                'slug' => 'lean-muscle-mass-nutrition-demo',
                'category' => 'Muscle Gain / Bulking',
                'type' => 'paid',
                'duration_days' => 42,
                'target_calories' => '2,600 – 3,100 kcal (tiered)',
                'price' => Money::fromRupees(5500),
                'summary' => 'Controlled surplus nutrition system designed to support progressive overload without excessive fat accumulation.',
                'description' => 'Demonstration muscle-building nutrition plan featuring training-day vs rest-day carbohydrate cycling and digestibly spaced protein feedings.',
                'sample_day' => [
                    ['meal' => 'Meal 1 — Breakfast', 'items' => '80g oats cooked in 250ml milk, 3 whole eggs, 1 tbsp peanut butter, 1 banana', 'macros' => '38g P · 78g C · 26g F'],
                ],
                'inclusions' => [
                    'Training vs rest day carbohydrate adjustments',
                    'High-calorie smoothie recipes without heavy digestive load',
                    'Pakistani & continental swap matrices',
                ],
                'dietary_tags' => ['Halal', 'Lean Surplus', '4–5 Meals/Day'],
                'disclaimer' => 'Educational nutrition framework for healthy adults. Not a medical diet.',
                'image_path' => '/images/demo/diet-meal-1200.webp',
                'is_featured' => true,
            ],
            [
                'title' => '1-to-1 Customised Nutrition Plan (With Coach Intake) [DEMO]',
                'slug' => 'customised-nutrition-plan-intake-demo',
                'category' => 'Customized Diet Plans',
                'type' => 'personalised',
                'duration_days' => 30,
                'target_calories' => 'Calculated from your intake',
                'price' => Money::fromRupees(12000),
                'summary' => 'Individually prepared meal architecture built by a qualified staff coach after reviewing your full intake, schedule, allergies and preferences.',
                'description' => 'Requires completion of the 12-point personalization intake after order confirmation. Any medical flags are routed for qualified professional review before plan preparation.',
                'sample_day' => [],
                'inclusions' => [
                    'Full personalization intake & staff review workflow',
                    'Custom meal schedule matched to your work/training hours',
                    '1 plan revision included within 30 days',
                ],
                'dietary_tags' => ['Individually Prepared', 'Staff Reviewed', 'Includes 1 Revision'],
                'disclaimer' => 'Coaches act strictly within verified qualifications. Medical conditions require physician clearance.',
                'image_path' => '/images/demo/diet-meal-1200.webp',
                'is_featured' => true,
            ],
            [
                'title' => 'Pakistani Kitchen General Fitness Starter Guide (Free) [DEMO]',
                'slug' => 'general-fitness-starter-guide-free-demo',
                'category' => 'General Fitness Nutrition',
                'type' => 'free',
                'duration_days' => 14,
                'target_calories' => '2,000 kcal baseline',
                'price' => 0,
                'summary' => 'Free introductory South Asian nutrition blueprint covering balanced protein, portion control, and hydration habits.',
                'description' => 'Complimentary nutrition resource demonstrating free entitlement access, portion tables, and basic household food swaps.',
                'sample_day' => [
                    ['meal' => 'Meal 1 — Breakfast', 'items' => '2 whole eggs + 1 egg white, 1 whole-wheat chapati (45g atta), 1 cup unsweetened chai', 'macros' => '24g P · 28g C · 11g F'],
                    ['meal' => 'Meal 2 — Lunch', 'items' => '150g grilled chicken/fish, 1 cup cooked daal, 120g boiled rice, salad', 'macros' => '48g P · 44g C · 9g F'],
                ],
                'inclusions' => ['Instant free access', 'Household portion guide', 'Basic protein swap list'],
                'dietary_tags' => ['Free Guide', 'Halal', 'Beginner Friendly'],
                'disclaimer' => 'Educational nutrition framework for healthy adults.',
                'image_path' => '/images/demo/diet-meal-1200.webp',
                'is_featured' => false,
            ],
            [
                'title' => '90-Day Body Recomposition Macro Blueprint [DEMO]',
                'slug' => 'body-recomposition-macro-blueprint-demo',
                'category' => 'Body Recomposition',
                'type' => 'paid',
                'duration_days' => 90,
                'target_calories' => '2,100 – 2,400 kcal',
                'price' => Money::fromRupees(6000),
                'summary' => 'High-protein, near-maintenance nutrition architecture for simultaneous fat reduction and strength progression.',
                'description' => 'Designed for intermediate lifters recomping around a 4-day or 5-day resistance training split.',
                'sample_day' => [
                    ['meal' => 'Meal 1 — Breakfast', 'items' => '4 egg whites + 2 whole eggs, 60g oats, 100g berries/apple', 'macros' => '34g P · 50g C · 12g F'],
                ],
                'inclusions' => ['Training vs rest day calorie targets', 'Approved South Asian swaps', 'Weekly grocery list'],
                'dietary_tags' => ['Halal', 'Recomp', 'High Protein'],
                'disclaimer' => 'Educational nutrition framework for healthy adults.',
                'image_path' => '/images/demo/diet-meal-1200.webp',
                'is_featured' => false,
            ],
            [
                'title' => 'Lean Muscle Hypertrophy Phase Protocol [DEMO]',
                'slug' => 'lean-muscle-hypertrophy-protocol-demo',
                'category' => 'Lean Muscle',
                'type' => 'paid',
                'duration_days' => 56,
                'target_calories' => '2,400 – 2,750 kcal',
                'price' => Money::fromRupees(5000),
                'summary' => '8-week lean-gain nutrition plan emphasizing peri-workout carbohydrates and 4 evenly spaced protein feedings.',
                'description' => 'Built for lifters who want lean tissue accretion while keeping waist circumference tightly controlled.',
                'sample_day' => [
                    ['meal' => 'Pre-Workout Meal', 'items' => '150g chicken breast, 180g cooked white rice, 1 banana', 'macros' => '46g P · 72g C · 4g F'],
                ],
                'inclusions' => ['Peri-workout meal timing', 'Approved swap matrix', 'Grocery checklist'],
                'dietary_tags' => ['Halal', 'Lean Gain'],
                'disclaimer' => 'Educational nutrition framework for healthy adults.',
                'image_path' => '/images/demo/diet-meal-1200.webp',
                'is_featured' => false,
            ],
            [
                'title' => 'Post-Cut Reverse & Maintenance Nutrition System [DEMO]',
                'slug' => 'post-cut-maintenance-nutrition-demo',
                'category' => 'Maintenance',
                'type' => 'paid',
                'duration_days' => 28,
                'target_calories' => '2,200 – 2,500 kcal',
                'price' => Money::fromRupees(4200),
                'summary' => 'Structured maintenance protocol to lock in fat-loss results and restore training volume after a calorie deficit.',
                'description' => 'Step-by-step carbohydrate and calorie stabilization schedule with flexible weekend dining guidance.',
                'sample_day' => [
                    ['meal' => 'Dinner', 'items' => '180g beef chapli/seekh kabab (air-fried/grilled), 2 chapatis, mint yogurt raita', 'macros' => '48g P · 56g C · 16g F'],
                ],
                'inclusions' => ['4-week maintenance stabilization schedule', 'Flexible social meal swaps'],
                'dietary_tags' => ['Halal', 'Maintenance'],
                'disclaimer' => 'Educational nutrition framework for healthy adults.',
                'image_path' => '/images/demo/diet-meal-1200.webp',
                'is_featured' => false,
            ],
        ];

        $defaultSubstitutions = [
            [
                'group' => 'Lean Protein (Per ~35g Protein Serving)',
                'original_item' => '150g Raw Chicken Breast',
                'swap_options' => ['160g White Fish (Rohu/Sole)', '140g Lean Beef Undercut', '6 Egg Whites + 1 Whole Egg', '1.5 Scoops Whey Isolate'],
                'notes' => 'Weigh raw before cooking; use max 1 tsp olive oil or desi ghee.',
            ],
            [
                'group' => 'Complex Carbohydrates (Per ~30g Carb Serving)',
                'original_item' => '1 Medium Whole-Wheat Chapati (45g dry atta)',
                'swap_options' => ['130g Cooked Basmati Rice', '180g Boiled Potato / Sweet Potato', '45g Rolled Oats', '2 Slices Whole-Grain Bread'],
                'notes' => 'Keep carbohydrate swaps iso-caloric on training and rest days.',
            ],
            [
                'group' => 'Healthy Fats (Per ~10g Fat Serving)',
                'original_item' => '15g Raw Almonds / Walnuts',
                'swap_options' => ['1 tbsp Natural Peanut Butter (16g)', '2 tsp Extra-Virgin Olive Oil / Desi Ghee', '2 Whole Egg Yolks'],
                'notes' => 'Measure cooking oils with a measuring spoon rather than free-pouring.',
            ],
        ];

        $defaultShoppingList = [
            'Proteins & Dairy' => ['Chicken breast fillets (2.5 kg/week)', 'Farm eggs (2.5 dozen/week)', 'Low-fat dahi / Greek yogurt (1.5 kg/week)', 'Lean beef qeema or white fish (1 kg/week)'],
            'Grains & Starches' => ['Whole-wheat chakki atta (1.5 kg)', 'Basmati rice (1 kg)', 'Rolled oats (500 g)', 'Potatoes / sweet potatoes (1 kg)'],
            'Produce & Aromatics' => ['Cucumbers, tomatoes, onions, green chilies, fresh coriander & mint', 'Spinach (palak), bhindi, lauki, seasonal greens', 'Bananas & apples (10–12 pieces)'],
            'Fats & Pantry Staples' => ['Moong / masoor daal (500 g)', 'Raw almonds (250 g)', 'Olive oil or pure desi ghee (measured)', 'Black pepper, zeera, turmeric, garlic, ginger, pink salt'],
        ];

        foreach ($dietPlans as $plan) {
            $dietModel = DietPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge($plan, ['status' => 'active', 'is_demo' => true])
            );

            if ($dietModel->type !== 'personalised') {
                DietPlanVersion::updateOrCreate(
                    ['diet_plan_id' => $dietModel->id, 'user_id' => null, 'version_number' => 1],
                    [
                        'title' => $dietModel->title.' — Master Edition v1',
                        'daily_calories' => 2000,
                        'protein_grams' => 165,
                        'carbs_grams' => 185,
                        'fat_grams' => 58,
                        'hydration_target_liters' => 3.5,
                        'meal_schedule' => ! empty($plan['sample_day']) ? $plan['sample_day'] : [
                            ['meal' => 'Meal 1 — Breakfast (08:30)', 'items' => '3 whole eggs + 2 egg whites, 1 chapati (45g atta)', 'macros' => '32g P · 28g C · 16g F'],
                        ],
                        'substitutions' => $defaultSubstitutions,
                        'shopping_list' => $defaultShoppingList,
                        'coach_notes' => 'Maintain 3.5L daily water intake and 8,000–10,000 daily steps.',
                        'revision_note' => 'Initial master version',
                        'prepared_by' => $manager->id,
                        'published_at' => now()->subDays(10),
                        'is_active' => true,
                        'is_demo' => true,
                    ]
                );
            }
        }

        // 8. Demo Workout Programs across all 7 required Workout Categories
        $workoutPrograms = [
            [
                'slug' => 'upper-lower-strength-hypertrophy-8wk-demo',
                'title' => '4-Day Upper / Lower Strength & Hypertrophy [DEMO]',
                'category' => 'Strength Training',
                'level' => 'Intermediate',
                'duration_weeks' => 8,
                'days_per_week' => 4,
                'equipment_required' => 'Barbell, Rack, Dumbbells, Cables',
                'price' => Money::fromRupees(4000),
                'summary' => 'Structured 8-week progressive overload template with RPE targets and warm-up protocols.',
                'structure' => [
                    'Day 1 (Mon): Upper Strength — Barbell Bench 4x5, Weighted Pull-Up 4x6, Overhead Press 3x8, Chest-Supported Row 3x10',
                    'Day 2 (Tue): Lower Strength — Back Squat 4x5, Romanian Deadlift 3x8, Walking Lunges 3x10/leg, Standing Calf Raise 4x12',
                    'Day 4 (Thu): Upper Hypertrophy — Incline DB Press 4x8-10, Lat Pulldown 4x10, Cable Lateral Raise 4x15, Arms Superset 3x12',
                    'Day 5 (Fri): Lower Hypertrophy — Hack Squat / Leg Press 4x10, Seated Leg Curl 4x12, Hip Thrust 3x10, Hanging Leg Raise 3x15',
                ],
                'is_featured' => true,
            ],
            [
                'slug' => 'push-pull-legs-hypertrophy-10wk-demo',
                'title' => '5-Day Push / Pull / Legs + Upper/Lower Hypertrophy [DEMO]',
                'category' => 'Muscle Building',
                'level' => 'Intermediate',
                'duration_weeks' => 10,
                'days_per_week' => 5,
                'equipment_required' => 'Full Gym',
                'price' => Money::fromRupees(4800),
                'summary' => 'High-volume hypertrophy block hitting every muscle group twice weekly with controlled eccentric tempos.',
                'structure' => ['Day 1: Push', 'Day 2: Pull', 'Day 3: Legs', 'Day 5: Upper Hypertrophy', 'Day 6: Lower Hypertrophy'],
                'is_featured' => true,
            ],
            [
                'slug' => 'beginner-full-body-foundations-6wk-demo',
                'title' => '3-Day Beginner Full-Body Gym Foundations [DEMO]',
                'category' => 'Beginner Fitness',
                'level' => 'Beginner',
                'duration_weeks' => 6,
                'days_per_week' => 3,
                'equipment_required' => 'Basic Gym or Dumbbells + Bench',
                'price' => Money::fromRupees(3200),
                'summary' => 'Movement-first 3-day full-body program teaching squat, hinge, press, pull, and bracing mechanics.',
                'structure' => ['Monday: Full Body A', 'Wednesday: Full Body B', 'Friday: Full Body C'],
                'is_featured' => false,
            ],
            [
                'slug' => 'minimal-equipment-home-workout-6wk-demo',
                'title' => '4-Day Minimal Equipment Home & Travel Protocol [DEMO]',
                'category' => 'Home Workouts',
                'level' => 'All Levels',
                'duration_weeks' => 6,
                'days_per_week' => 4,
                'equipment_required' => 'Adjustable Dumbbells & Resistance Bands',
                'price' => Money::fromRupees(3500),
                'summary' => 'Zero-machine home training system designed for busy schedules with 45-minute sessions.',
                'structure' => ['Day 1: Upper Push/Pull', 'Day 2: Lower Unilateral', 'Day 4: Upper Metabolic', 'Day 5: Lower + Core'],
                'is_featured' => false,
            ],
            [
                'slug' => 'metabolic-fat-loss-resistance-8wk-demo',
                'title' => '4-Day Fat-Loss Resistance & Conditioning Block [DEMO]',
                'category' => 'Fat Loss Training',
                'level' => 'Intermediate',
                'duration_weeks' => 8,
                'days_per_week' => 4,
                'equipment_required' => 'Full Gym',
                'price' => Money::fromRupees(4200),
                'summary' => 'Strength-preserving resistance sessions paired with structured low-impact conditioning intervals.',
                'structure' => ['Day 1: Lower Strength + Liss', 'Day 2: Upper Strength', 'Day 4: Full Body Density', 'Day 6: Conditioning + Core'],
                'is_featured' => false,
            ],
            [
                'slug' => 'advanced-peaking-powerbuilding-12wk-demo',
                'title' => '5-Day Advanced Powerbuilding & Strength Peaking [DEMO]',
                'category' => 'Advanced Training',
                'level' => 'Advanced',
                'duration_weeks' => 12,
                'days_per_week' => 5,
                'equipment_required' => 'Barbell, Competition Rack, Full Gym',
                'price' => Money::fromRupees(6500),
                'summary' => '12-week wave-loaded squat, bench, and deadlift progression combined with targeted accessory hypertrophy.',
                'structure' => ['Day 1: Heavy Squat + Quad', 'Day 2: Heavy Bench + Chest/Triceps', 'Day 3: Heavy Deadlift + Back', 'Day 5: Overhead + Upper Back', 'Day 6: Hypertrophy Weak-Point'],
                'is_featured' => false,
            ],
            [
                'slug' => 'joint-mobility-and-recovery-protocol-demo',
                'title' => 'Daily Thoracic, Hip & Shoulder Mobility Routine [DEMO]',
                'category' => 'Mobility and Recovery',
                'level' => 'All Levels',
                'duration_weeks' => 4,
                'days_per_week' => 4,
                'equipment_required' => 'Bodyweight, Foam Roller & Light Band',
                'price' => Money::fromRupees(2500),
                'summary' => '20-minute structured mobility flows to improve overhead positioning, hip internal rotation, and squat depth.',
                'structure' => ['Flow 1: Hip & Ankle Squat Prep', 'Flow 2: Thoracic & Overhead Press Prep', 'Flow 3: Posterior Chain Decompression', 'Flow 4: Active Recovery'],
                'is_featured' => false,
            ],
        ];

        foreach ($workoutPrograms as $wp) {
            WorkoutProgram::updateOrCreate(
                ['slug' => $wp['slug']],
                array_merge($wp, ['status' => 'active', 'is_demo' => true])
            );
        }

        // 9. Demo Gym Membership Plans & Opening Hours
        $memberships = [
            [
                'name' => 'Monthly Gym Floor Access [DEMO]',
                'slug' => 'monthly-gym-access-demo',
                'duration_days' => 30,
                'price' => Money::fromRupees(8000),
                'registration_fee' => Money::fromRupees(3000),
                'access_hours' => 'All Operating Hours',
                'inclusions' => ['Full strength & cardio floor access', 'Locker room access', '1 onboarding equipment orientation'],
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Quarterly Committed Membership [DEMO]',
                'slug' => 'quarterly-gym-access-demo',
                'duration_days' => 90,
                'price' => Money::fromRupees(21000),
                'registration_fee' => 0,
                'access_hours' => 'All Operating Hours',
                'inclusions' => ['Registration fee waived', 'Full strength & conditioning zones', 'Monthly body-composition log', 'Group barbell clinic booking access'],
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Annual Executive Strength Membership [DEMO]',
                'slug' => 'annual-executive-gym-access-demo',
                'duration_days' => 365,
                'price' => Money::fromRupees(72000),
                'registration_fee' => 0,
                'access_hours' => 'All Operating Hours + Priority Class Booking',
                'inclusions' => ['Registration fee waived', 'Priority class & barbell clinic booking', 'Quarterly 1-on-1 technique assessment', 'Dedicated locker reservation option'],
                'is_featured' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($memberships as $m) {
            MembershipPlan::updateOrCreate(
                ['slug' => $m['slug']],
                array_merge($m, ['status' => 'active', 'is_demo' => true])
            );
        }

        $days = [
            [1, 'Monday', '06:00:00', '23:00:00', '11:00 – 14:00 (Pending Confirmation)', '18:00 – 21:30', false, 'Demo schedule'],
            [2, 'Tuesday', '06:00:00', '23:00:00', '11:00 – 14:00 (Pending Confirmation)', '18:00 – 21:30', false, 'Demo schedule'],
            [3, 'Wednesday', '06:00:00', '23:00:00', '11:00 – 14:00 (Pending Confirmation)', '18:00 – 21:30', false, 'Demo schedule'],
            [4, 'Thursday', '06:00:00', '23:00:00', '11:00 – 14:00 (Pending Confirmation)', '18:00 – 21:30', false, 'Demo schedule'],
            [5, 'Friday', '06:00:00', '23:00:00', '11:00 – 13:00 (Closed 13:00–14:30 Jummah)', '18:00 – 21:30', false, 'Jummah break 13:00–14:30'],
            [6, 'Saturday', '07:00:00', '22:00:00', null, '17:00 – 20:30', false, 'Demo schedule'],
            [7, 'Sunday', '09:00:00', '18:00:00', null, null, false, 'Open recovery & strength hours (Demo)'],
        ];

        foreach ($days as [$num, $name, $open, $close, $ladies, $peak, $closed, $note]) {
            OpeningHour::updateOrCreate(
                ['day_of_week' => $num],
                [
                    'day_name' => $name,
                    'opens_at' => $open,
                    'closes_at' => $close,
                    'ladies_only_window' => $ladies,
                    'peak_window' => $peak,
                    'is_closed' => $closed,
                    'note' => $note,
                ]
            );
        }

        // 10. Demo Free Training Video Previews
        $videos = [
            [
                'title' => 'Barbell Back Squat — Bracing, Bar Path & Depth Standards [DEMO]',
                'slug' => 'barbell-back-squat-standards-demo',
                'category' => 'Strength Technique',
                'access' => 'public',
                'duration_seconds' => 265,
                'coach_note' => 'Free technical preview · 360° intra-abdominal bracing cueing',
                'summary' => 'How to set upper-back shelf tension, control eccentric tempo, and hit repeatable squat depth safely.',
                'thumbnail_path' => '/images/demo/hero-gym-768.webp',
                'is_featured' => true,
            ],
            [
                'title' => 'Romanian Deadlift — Hip Hinge vs Lower-Back Takeover [DEMO]',
                'slug' => 'romanian-deadlift-hinge-demo',
                'category' => 'Hypertrophy Execution',
                'access' => 'public',
                'duration_seconds' => 210,
                'coach_note' => 'Free technical preview · Posterior chain loading',
                'summary' => 'Maintaining lats locked and bar contact along the thigh to isolate hamstrings and glutes.',
                'thumbnail_path' => '/images/demo/route-gym-800.webp',
                'is_featured' => true,
            ],
            [
                'title' => 'Weekly Check-In & Biofeedback Walkthrough [DEMO]',
                'slug' => 'weekly-checkin-walkthrough-demo',
                'category' => 'Coaching Systems',
                'access' => 'public',
                'duration_seconds' => 195,
                'coach_note' => 'Free preview · How online coaching accountability works',
                'summary' => 'Inside the BIN MALIK FITNESS weekly check-in process: scale weight trends, training logs, and nutrition adjustments.',
                'thumbnail_path' => '/images/demo/route-coaching-800.webp',
                'is_featured' => true,
            ],
            [
                'title' => 'Incline Dumbbell Press — Scapular Plane & Humeral Path [DEMO]',
                'slug' => 'incline-db-press-scapular-plane-entitled',
                'category' => 'Hypertrophy Execution',
                'access' => 'entitled',
                'duration_seconds' => 340,
                'coach_note' => 'Members Only · Requires active coaching or video entitlement',
                'summary' => 'Detailed breakdown of bench angle (30° vs 45°), elbow tuck, and deep stretch pause without anterior deltoid takeover.',
                'thumbnail_path' => '/images/demo/hero-gym-768.webp',
                'is_featured' => true,
            ],
            [
                'title' => 'Competition Bench & Leg Drive Biomechanics Masterclass [DEMO]',
                'slug' => 'competition-bench-leg-drive-entitled',
                'category' => 'Strength Technique',
                'access' => 'entitled',
                'duration_seconds' => 420,
                'coach_note' => 'Members Only · Server-side entitlement verified',
                'summary' => 'Full arch setup, lat depression, bar touch point, and leg drive timing for maximal pressing strength.',
                'thumbnail_path' => '/images/demo/route-gym-800.webp',
                'is_featured' => true,
            ],
        ];

        foreach ($videos as $vid) {
            Video::updateOrCreate(
                ['slug' => $vid['slug']],
                array_merge($vid, ['provider' => 'external_private', 'is_demo' => true])
            );
        }

        // 11. FAQs
        $faqs = [
            [
                'group' => 'Coaching',
                'question' => 'How does BIN MALIK FITNESS online coaching work after I sign up?',
                'answer' => 'Once your order payment is verified by our staff (via bank transfer confirmation), your coaching subscription is activated, a qualified coach is assigned to your account, and you complete a structured 12-point intake covering your training history, schedule, equipment, and local food preferences.',
                'sort_order' => 1,
            ],
            [
                'group' => 'Supplements',
                'question' => 'How do you verify supplement authenticity and expiry dates?',
                'answer' => 'Every supplement product tracks importer details, batch/lot numbers, and expiry dates in our inventory system. We do not publish fabricated certifications—each product listing displays its current regulatory and supplier verification status clearly.',
                'sort_order' => 2,
            ],
            [
                'group' => 'Payments',
                'question' => 'What payment methods are accepted in Pakistan?',
                'answer' => 'At launch we support Cash on Delivery (for eligible physical supplement orders) and Manual Bank Transfer with staff verification. Uploaded transfer receipts are reviewed by operations staff before an order or subscription is marked paid.',
                'sort_order' => 3,
            ],
            [
                'group' => 'Privacy',
                'question' => 'Are my check-in measurements and progress photos kept private?',
                'answer' => 'Yes. All client check-ins, measurements, and progress photos are stored on private storage and are strictly private by default. Nothing is ever displayed publicly without a separate, explicitly documented consent record.',
                'sort_order' => 4,
            ],
            [
                'group' => 'Diet Plans',
                'question' => 'Are the diet plans built around Pakistani meals or imported foods?',
                'answer' => 'Our nutrition plans are built around everyday Pakistani kitchen staples—whole-wheat chapati, basmati rice, daal, chicken, beef, eggs, yogurt, and seasonal produce—with clear gram and household portion equivalents plus approved swap tables.',
                'sort_order' => 5,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['is_published' => true, 'is_demo' => true])
            );
        }

        // 12. Demo Coupons & Sample Orders for Stage 3 Ecommerce Operations
        Coupon::updateOrCreate(
            ['code' => 'BMF10'],
            [
                'description' => '10% off supplement orders over PKR 5,000 [DEMO]',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_subtotal_paisa' => Money::fromRupees(5000),
                'max_discount_paisa' => Money::fromRupees(3500),
                'usage_limit' => 100,
                'per_user_limit' => 3,
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
                'created_by' => $admin->id,
                'is_demo' => true,
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'DEMO1500'],
            [
                'description' => 'PKR 1,500 flat discount on orders over PKR 15,000 [DEMO]',
                'discount_type' => 'fixed_amount',
                'discount_value' => Money::fromRupees(1500),
                'min_subtotal_paisa' => Money::fromRupees(15000),
                'max_discount_paisa' => Money::fromRupees(1500),
                'usage_limit' => 50,
                'per_user_limit' => 1,
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
                'created_by' => $admin->id,
                'is_demo' => true,
            ]
        );

        $wheyVariant = ProductVariant::where('sku', 'DEMO-WPI-5LB-CHOC')->first();
        $creatineVariant = ProductVariant::where('sku', 'DEMO-CRE-300G-UNF')->first();

        if ($wheyVariant && $creatineVariant) {
            $deliveredOrder = Order::updateOrCreate(
                ['order_number' => 'BMF-DEMO-1001'],
                [
                    'access_token' => 'demo-token-bmf-1001-verified-cod-order-access',
                    'user_id' => $customer->id,
                    'assigned_manager_id' => $manager->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'shipping_address_snapshot' => [
                        'recipient_name' => $customer->name,
                        'phone' => $customer->phone,
                        'line1' => 'House 14, Street 6, Sector Demo (Placeholder)',
                        'area' => 'Model Town',
                        'city' => 'Lahore',
                        'province' => 'Punjab',
                        'postal_code' => '54000',
                    ],
                    'shipping_method' => 'standard_courier',
                    'payment_method' => 'cod',
                    'status' => Order::STATUS_DELIVERED,
                    'payment_status' => Order::PAYMENT_VERIFIED,
                    'currency' => 'PKR',
                    'subtotal_paisa' => $creatineVariant->price,
                    'discount_paisa' => 0,
                    'shipping_paisa' => Money::fromRupees(350),
                    'tax_paisa' => 0,
                    'total_paisa' => $creatineVariant->price + Money::fromRupees(350),
                    'refunded_paisa' => 0,
                    'courier_name' => 'TCS Express (Demo)',
                    'tracking_number' => 'TCS-DEMO-908172',
                    'placed_at' => now()->subDays(6),
                    'confirmed_at' => now()->subDays(6),
                    'shipped_at' => now()->subDays(5),
                    'delivered_at' => now()->subDays(3),
                    'is_demo' => true,
                ]
            );

            OrderItem::updateOrCreate(
                ['order_id' => $deliveredOrder->id, 'product_variant_id' => $creatineVariant->id],
                [
                    'product_id' => $creatineVariant->product_id,
                    'product_name_snapshot' => $creatineVariant->product->name,
                    'sku_snapshot' => $creatineVariant->sku,
                    'variant_label_snapshot' => $creatineVariant->label(),
                    'batch_number_snapshot' => 'DEMO-LOT-2026A',
                    'expiry_date_snapshot' => now()->addMonths(14)->toDateString(),
                    'quantity' => 1,
                    'unit_price_paisa' => $creatineVariant->price,
                    'line_total_paisa' => $creatineVariant->price,
                    'weight_grams_snapshot' => 500,
                ]
            );

            Payment::updateOrCreate(
                ['order_id' => $deliveredOrder->id, 'method' => 'cod'],
                [
                    'status' => 'verified',
                    'amount_paisa' => $deliveredOrder->total_paisa,
                    'currency' => 'PKR',
                    'transaction_reference' => 'COD-COLLECTED-1001',
                    'verified_by' => $manager->id,
                    'verified_at' => now()->subDays(3),
                    'staff_notes' => 'Seeded demo COD delivery payment confirmation.',
                ]
            );

            $pendingBankOrder = Order::updateOrCreate(
                ['order_number' => 'BMF-DEMO-1002'],
                [
                    'access_token' => 'demo-token-bmf-1002-bank-transfer-order-access',
                    'user_id' => $customer->id,
                    'assigned_manager_id' => $manager->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'shipping_address_snapshot' => [
                        'recipient_name' => $customer->name,
                        'phone' => $customer->phone,
                        'line1' => 'House 14, Street 6, Sector Demo (Placeholder)',
                        'area' => 'Model Town',
                        'city' => 'Lahore',
                        'province' => 'Punjab',
                        'postal_code' => '54000',
                    ],
                    'shipping_method' => 'standard_courier',
                    'payment_method' => 'bank_transfer',
                    'status' => Order::STATUS_AWAITING_PAYMENT,
                    'payment_status' => Order::PAYMENT_PROOF_SUBMITTED,
                    'currency' => 'PKR',
                    'subtotal_paisa' => $wheyVariant->price,
                    'discount_paisa' => 0,
                    'shipping_paisa' => 0,
                    'tax_paisa' => 0,
                    'total_paisa' => $wheyVariant->price,
                    'refunded_paisa' => 0,
                    'placed_at' => now()->subHours(4),
                    'is_demo' => true,
                ]
            );

            OrderItem::updateOrCreate(
                ['order_id' => $pendingBankOrder->id, 'product_variant_id' => $wheyVariant->id],
                [
                    'product_id' => $wheyVariant->product_id,
                    'product_name_snapshot' => $wheyVariant->product->name,
                    'sku_snapshot' => $wheyVariant->sku,
                    'variant_label_snapshot' => $wheyVariant->label(),
                    'batch_number_snapshot' => 'DEMO-LOT-2026A',
                    'expiry_date_snapshot' => now()->addMonths(14)->toDateString(),
                    'quantity' => 1,
                    'unit_price_paisa' => $wheyVariant->price,
                    'line_total_paisa' => $wheyVariant->price,
                    'weight_grams_snapshot' => 1000,
                ]
            );

            Payment::updateOrCreate(
                ['order_id' => $pendingBankOrder->id, 'method' => 'bank_transfer'],
                [
                    'status' => 'proof_submitted',
                    'amount_paisa' => $pendingBankOrder->total_paisa,
                    'currency' => 'PKR',
                    'transaction_reference' => 'MEEZAN-DEMO-TXN-88219',
                    'sender_bank_name' => 'Meezan Bank',
                    'sender_account_last4' => '4419',
                    'staff_notes' => 'Awaiting staff verification (Never marked paid automatically).',
                ]
            );
        }

        // 13. Stage 4 Demo Coaching Subscription, Intake, Personalised Diet Plan, Workout, Check-Ins & Messages
        $recompPackage = CoachingPackage::where('slug', '12-week-complete-recomposition')->first();
        $customDietPlan = DietPlan::where('slug', 'customised-nutrition-plan-intake-demo')->first();
        $fatLossDietPlan = DietPlan::where('slug', 'pakistani-fat-loss-blueprint-demo')->first();
        $upperLowerProgram = WorkoutProgram::where('slug', 'upper-lower-strength-hypertrophy-8wk-demo')->first();

        if ($recompPackage && $customDietPlan && $fatLossDietPlan && $upperLowerProgram) {
            $coach = $coachOnly;

            $demoSub = CoachingSubscription::updateOrCreate(
                ['subscription_number' => 'SUB-DEMO-1001'],
                [
                    'user_id' => $customer->id,
                    'coaching_package_id' => $recompPackage->id,
                    'coach_id' => $coach->id,
                    'status' => CoachingSubscription::STATUS_ACTIVE,
                    'starts_at' => now()->subDays(21),
                    'ends_at' => now()->addDays(63),
                    'pause_days_used' => 0,
                    'max_pause_days' => 14,
                    'onboarding_completed_at' => now()->subDays(20),
                    'coach_summary_notes' => 'Client is responding well to 2,050 kcal recomposition macro targets and 4-day Upper/Lower split.',
                    'is_demo' => true,
                ]
            );

            $intakeConsent = ConsentRecord::firstOrCreate(
                ['user_id' => $customer->id, 'type' => 'health_intake_processing'],
                [
                    'granted_at' => now()->subDays(20),
                    'evidence_note' => 'Seeded Stage 4 demo intake health consent',
                    'ip_address' => '127.0.0.1',
                ]
            );

            $demoIntake = CoachingIntake::updateOrCreate(
                ['user_id' => $customer->id, 'coaching_subscription_id' => $demoSub->id],
                [
                    'diet_plan_id' => $customDietPlan->id,
                    'fitness_goal' => 'Body Recomposition',
                    'age' => 29,
                    'height_cm' => 178,
                    'weight_kg' => 86.4,
                    'activity_level' => 'Moderately Active (8,500 steps/day)',
                    'training_experience' => 'Intermediate (2+ years lifting)',
                    'food_preferences' => 'Halal South Asian home meals: chicken breast, lean beef qeema, eggs, whole-wheat chapati, basmati rice, dahi',
                    'allergies' => 'None',
                    'dietary_restrictions' => 'Halal only; prefers 4 meals per day',
                    'equipment_availability' => 'Full Commercial Gym (Barbell, Rack, Dumbbells, Cables)',
                    'preferred_schedule' => 'Mon / Tue / Thu / Fri evenings (18:30 PKT)',
                    'health_consent_granted' => true,
                    'consent_record_id' => $intakeConsent->id,
                    'health_notes_encrypted' => 'Mild left shoulder tightness on wide-grip upright rows; no medical conditions.',
                    'needs_professional_review' => false,
                    'review_status' => CoachingIntake::STATUS_PLAN_DELIVERED,
                    'reviewed_by' => $coach->id,
                    'reviewed_at' => now()->subDays(19),
                    'reviewer_notes' => 'Cleared for progressive strength training and 2,050 kcal recomposition diet.',
                ]
            );

            $customVersion = DietPlanVersion::updateOrCreate(
                ['diet_plan_id' => $customDietPlan->id, 'user_id' => $customer->id, 'version_number' => 1],
                [
                    'title' => 'Personalised Recomposition Meal Architecture — Revision v1 [DEMO]',
                    'daily_calories' => 2050,
                    'protein_grams' => 175,
                    'carbs_grams' => 190,
                    'fat_grams' => 62,
                    'hydration_target_liters' => 3.8,
                    'meal_schedule' => [
                        ['meal' => 'Meal 1 — Breakfast (08:30)', 'items' => '3 whole eggs + 3 egg whites omelette with spinach, 1 whole-wheat chapati (50g atta), black coffee', 'macros' => '36g P · 32g C · 16g F · ~415 kcal'],
                        ['meal' => 'Meal 2 — Lunch (13:30)', 'items' => '200g raw-weight grilled chicken tikka breast, 160g cooked basmati rice, 100g mint-cucumber raita', 'macros' => '60g P · 48g C · 9g F · ~515 kcal'],
                        ['meal' => 'Meal 3 — Pre-Workout (17:15)', 'items' => '1 scoop whey isolate in water + 1 large banana + 10g raw almonds', 'macros' => '27g P · 32g C · 7g F · ~300 kcal'],
                        ['meal' => 'Meal 4 — Post-Workout Dinner (20:30)', 'items' => '180g lean beef qeema (1 tsp desi ghee), 1 whole-wheat chapati, 1 cup cooked moong daal, kachumber salad', 'macros' => '52g P · 78g C · 30g F · ~820 kcal'],
                    ],
                    'substitutions' => $defaultSubstitutions,
                    'shopping_list' => $defaultShoppingList,
                    'coach_notes' => 'Keep sodium consistent across training and rest days. Weigh chicken and beef raw.',
                    'revision_note' => 'Initial personalised plan prepared from 12-point intake',
                    'prepared_by' => $coach->id,
                    'published_at' => now()->subDays(19),
                    'is_active' => true,
                    'is_demo' => true,
                ]
            );

            $planRequest = PersonalisedPlanRequest::updateOrCreate(
                ['user_id' => $customer->id, 'diet_plan_id' => $customDietPlan->id],
                [
                    'coaching_intake_id' => $demoIntake->id,
                    'assigned_coach_id' => $coach->id,
                    'delivered_version_id' => $customVersion->id,
                    'status' => PersonalisedPlanRequest::STATUS_DELIVERED,
                    'custom_substitution_overrides' => [
                        'Lean Protein (Per ~35g Protein Serving)' => '160g White Fish (Rohu/Sole)',
                    ],
                    'delivered_at' => now()->subDays(19),
                    'is_demo' => true,
                ]
            );

            // Grant active entitlements for customer
            foreach ([$recompPackage, $customDietPlan, $customVersion, $fatLossDietPlan, $upperLowerProgram] as $target) {
                Entitlement::updateOrCreate(
                    [
                        'user_id' => $customer->id,
                        'entitleable_type' => $target::class,
                        'entitleable_id' => $target->id,
                        'source_type' => CoachingSubscription::class,
                        'source_id' => $demoSub->id,
                    ],
                    [
                        'status' => Entitlement::STATUS_ACTIVE,
                        'starts_at' => now()->subDays(20),
                        'ends_at' => now()->addDays(63),
                    ]
                );
            }

            WorkoutAssignment::updateOrCreate(
                ['user_id' => $customer->id, 'workout_program_id' => $upperLowerProgram->id],
                [
                    'coaching_subscription_id' => $demoSub->id,
                    'assigned_by' => $coach->id,
                    'title_override' => 'Phase 1: 4-Day Upper / Lower Recomposition Block [DEMO]',
                    'scheduled_days' => $upperLowerProgram->structure,
                    'custom_guidance' => 'Substitute neutral-grip dumbbell press if left shoulder feels tight. Keep top sets at RPE 8.',
                    'target_sessions_per_week' => 4,
                    'completed_sessions_count' => 8,
                    'last_completed_at' => now()->subDay(),
                    'last_session_log' => [
                        'day_label' => 'Day 2 (Tue): Lower Strength',
                        'duration_minutes' => 65,
                        'rpe' => 8,
                        'notes' => 'Back squat 110kg 4x5 moved cleanly with solid bracing.',
                        'logged_at' => now()->subDay()->toIso8601String(),
                    ],
                    'status' => 'active',
                    'starts_on' => now()->subDays(20)->toDateString(),
                    'ends_on' => now()->addDays(36)->toDateString(),
                ]
            );

            $checkInSeries = [
                ['days_ago' => 14, 'weight' => 86.4, 'waist' => 92.0, 'adherence' => 8, 'energy' => 8, 'workouts' => 4, 'notes' => 'Week 1 baseline check-in. Hitting all 4 meals consistently.', 'feedback' => 'Strong week 1 adherence. Keep calories at 2,050 kcal and focus on squat depth.'],
                ['days_ago' => 7, 'weight' => 85.1, 'waist' => 90.5, 'adherence' => 9, 'energy' => 8, 'workouts' => 4, 'notes' => 'Down 1.3 kg scale weight and waist visibly tighter.', 'feedback' => 'Excellent rate of loss (-1.3 kg) with strength increasing on bench and RDL. No macro changes needed.'],
                ['days_ago' => 1, 'weight' => 83.9, 'waist' => 89.0, 'adherence' => 9, 'energy' => 9, 'workouts' => 4, 'notes' => 'Hit 110kgx5 on squat this week. Recovery and sleep (7.5h) are dialled in.', 'feedback' => 'Down 2.5 kg and -3.0 cm off the waist in 3 weeks while gaining strength. Keep pushing!'],
            ];

            foreach ($checkInSeries as $row) {
                CoachingCheckIn::updateOrCreate(
                    [
                        'user_id' => $customer->id,
                        'check_in_date' => now()->subDays($row['days_ago'])->toDateString(),
                    ],
                    [
                        'coaching_subscription_id' => $demoSub->id,
                        'weight_kg' => $row['weight'],
                        'waist_cm' => $row['waist'],
                        'chest_cm' => 106.0,
                        'arms_cm' => 38.5,
                        'body_fat_pct' => 18.5,
                        'sleep_hours' => 7.5,
                        'water_liters' => 3.8,
                        'step_count' => 9200,
                        'diet_adherence_score' => $row['adherence'],
                        'energy_score' => $row['energy'],
                        'workouts_completed_this_week' => $row['workouts'],
                        'client_notes' => $row['notes'],
                        'is_public_display_consented' => false,
                        'coach_feedback' => $row['feedback'],
                        'reviewed_by' => $coach->id,
                        'reviewed_at' => now()->subDays(max(0, $row['days_ago'] - 1)),
                        'status' => 'reviewed',
                        'is_demo' => true,
                    ]
                );
            }

            CoachingConsultation::updateOrCreate(
                ['user_id' => $customer->id, 'topic' => 'Mid-Block Technique & Macro Review [DEMO]'],
                [
                    'coaching_subscription_id' => $demoSub->id,
                    'coach_id' => $coach->id,
                    'scheduled_at' => now()->addDays(3)->setTime(19, 0),
                    'duration_minutes' => 30,
                    'channel' => 'whatsapp_video',
                    'client_notes' => 'Would like to review deadlift hip hinge setup and weekend travel meal swaps.',
                    'coach_summary' => 'Confirmed for 7:00 PM PKT.',
                    'meeting_reference' => 'WhatsApp Video Call Slot #BMF-402',
                    'status' => CoachingConsultation::STATUS_CONFIRMED,
                    'is_demo' => true,
                ]
            );

            if (! CoachingMessage::where('client_id', $customer->id)->exists()) {
                CoachingMessage::create([
                    'client_id' => $customer->id,
                    'coaching_subscription_id' => $demoSub->id,
                    'sender_id' => $customer->id,
                    'body' => 'Assalam-o-Alaikum Coach! Can I swap the 200g chicken breast at lunch for white fish twice this week?',
                    'read_at' => now()->subHours(5),
                    'is_demo' => true,
                ]);

                CoachingMessage::create([
                    'client_id' => $customer->id,
                    'coaching_subscription_id' => $demoSub->id,
                    'sender_id' => $coach->id,
                    'body' => 'Walaikum Assalam! Yes—use 215g raw white fish (rohu/sole) grilled with 1 tsp olive oil as a 1:1 protein swap.',
                    'read_at' => now()->subHours(3),
                    'is_demo' => true,
                ]);
            }
        }

        // 14. Stage 5 Demo Physical Gym Membership, Check-Ins, Classes, Bookings, Holidays & Trial Enquiries
        $quarterlyPlan = MembershipPlan::where('slug', 'quarterly-gym-access-demo')->first();
        $monthlyPlan = MembershipPlan::where('slug', 'monthly-gym-access-demo')->first();

        if ($quarterlyPlan && $monthlyPlan) {
            $gymOrder = Order::updateOrCreate(
                ['order_number' => 'BMF-GYM-DEMO-1001'],
                [
                    'access_token' => 'demo-token-bmf-gym-1001-verified-counter-order',
                    'user_id' => $customer->id,
                    'assigned_manager_id' => $manager->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'shipping_address_snapshot' => [
                        'recipient_name' => $customer->name,
                        'phone' => $customer->phone,
                        'line1' => 'Physical Gym Membership Desk',
                        'city' => 'Configurable Gym City',
                        'province' => 'Punjab',
                    ],
                    'shipping_method' => 'in_gym_counter',
                    'payment_method' => 'counter_cash',
                    'status' => Order::STATUS_DELIVERED,
                    'payment_status' => Order::PAYMENT_VERIFIED,
                    'business_line' => 'gym',
                    'currency' => 'PKR',
                    'subtotal_paisa' => $quarterlyPlan->price,
                    'discount_paisa' => 0,
                    'shipping_paisa' => 0,
                    'tax_paisa' => 0,
                    'total_paisa' => $quarterlyPlan->price,
                    'customer_notes' => 'Quarterly Committed Membership verified at physical gym counter [DEMO]',
                    'placed_at' => now()->subDays(18),
                    'confirmed_at' => now()->subDays(18),
                    'delivered_at' => now()->subDays(18),
                    'is_demo' => true,
                ]
            );

            OrderItem::updateOrCreate(
                [
                    'order_id' => $gymOrder->id,
                    'purchasable_type' => MembershipPlan::class,
                    'purchasable_id' => $quarterlyPlan->id,
                ],
                [
                    'business_line' => 'gym',
                    'product_name_snapshot' => $quarterlyPlan->name,
                    'sku_snapshot' => 'GYM-QUARTERLY-DEMO',
                    'variant_label_snapshot' => '90 Days · All Operating Hours',
                    'quantity' => 1,
                    'unit_price_paisa' => $quarterlyPlan->price,
                    'line_total_paisa' => $quarterlyPlan->price,
                    'weight_grams_snapshot' => 0,
                ]
            );

            Payment::updateOrCreate(
                ['order_id' => $gymOrder->id, 'method' => 'counter_cash'],
                [
                    'status' => 'verified',
                    'amount_paisa' => $quarterlyPlan->price,
                    'currency' => 'PKR',
                    'transaction_reference' => 'COUNTER-REC-DEMO-401',
                    'verified_by' => $manager->id,
                    'verified_at' => now()->subDays(18),
                    'staff_notes' => 'Verified offline at gym counter reception [DEMO].',
                ]
            );

            $demoMembership = GymMembership::updateOrCreate(
                ['membership_number' => 'GYM-DEMO-1001'],
                [
                    'member_card_code' => 'BMF-CARD-1001',
                    'user_id' => $customer->id,
                    'membership_plan_id' => $quarterlyPlan->id,
                    'order_id' => $gymOrder->id,
                    'assigned_trainer_id' => $coachOnly->id,
                    'status' => GymMembership::STATUS_ACTIVE,
                    'starts_at' => now()->subDays(18),
                    'ends_at' => now()->addDays(72),
                    'last_check_in_at' => now()->subHours(18),
                    'total_visits_count' => 3,
                    'staff_notes' => 'Active quarterly strength member. Cleared for barbell clinic sessions.',
                    'is_demo' => true,
                ]
            );

            Entitlement::updateOrCreate(
                [
                    'user_id' => $customer->id,
                    'entitleable_type' => MembershipPlan::class,
                    'entitleable_id' => $quarterlyPlan->id,
                    'source_type' => GymMembership::class,
                    'source_id' => $demoMembership->id,
                ],
                [
                    'status' => Entitlement::STATUS_ACTIVE,
                    'starts_at' => $demoMembership->starts_at,
                    'ends_at' => $demoMembership->ends_at,
                ]
            );

            $checkInRows = [
                ['hours_ago' => 90, 'method' => 'desk_manual', 'nonce' => null, 'note' => 'Reception card verification — Lower body session'],
                ['hours_ago' => 42, 'method' => 'qr_staff_verified', 'nonce' => 'demo-seed-qr-nonce-001', 'note' => 'Signed QR pass verified by desk manager'],
                ['hours_ago' => 18, 'method' => 'qr_staff_verified', 'nonce' => 'demo-seed-qr-nonce-002', 'note' => 'Signed QR pass verified — Barbell pressing day'],
            ];

            foreach ($checkInRows as $idx => $ci) {
                GymCheckIn::updateOrCreate(
                    [
                        'gym_membership_id' => $demoMembership->id,
                        'entry_note' => $ci['note'],
                    ],
                    [
                        'user_id' => $customer->id,
                        'recorded_by' => $manager->id,
                        'check_in_method' => $ci['method'],
                        'qr_nonce' => $ci['nonce'],
                        'checked_in_at' => now()->subHours($ci['hours_ago']),
                        'checked_out_at' => now()->subHours(max(1, $ci['hours_ago'] - 2)),
                        'verification_status' => 'admitted',
                    ]
                );
            }

            $gymClasses = [
                [
                    'slug' => 'barbell-squat-deadlift-clinic-demo',
                    'title' => 'Barbell Squat & Deadlift Technique Clinic [DEMO]',
                    'category' => 'Barbell Strength',
                    'instructor_id' => $coachOnly->id,
                    'day_of_week' => 2,
                    'starts_at_time' => '19:00:00',
                    'ends_at_time' => '20:15:00',
                    'session_date' => now()->addDays(2)->toDateString(),
                    'room_or_zone' => 'Main Barbell Platform Zone',
                    'capacity' => 12,
                    'booked_count' => 1,
                    'requires_active_membership' => true,
                    'summary' => 'Small-group competition rack coaching on bracing, bar path, and hip hinge mechanics.',
                ],
                [
                    'slug' => 'upper-hypertrophy-execution-lab-demo',
                    'title' => 'Upper Hypertrophy & Tempo Execution Lab [DEMO]',
                    'category' => 'Hypertrophy Foundations',
                    'instructor_id' => $manager->id,
                    'day_of_week' => 4,
                    'starts_at_time' => '18:30:00',
                    'ends_at_time' => '19:45:00',
                    'session_date' => now()->addDays(4)->toDateString(),
                    'room_or_zone' => 'Free-Weight & Cable Bay',
                    'capacity' => 14,
                    'booked_count' => 0,
                    'requires_active_membership' => true,
                    'summary' => 'Controlled eccentric pressing and rowing mechanics for shoulder-safe hypertrophy.',
                ],
                [
                    'slug' => 'saturday-sled-metabolic-conditioning-demo',
                    'title' => 'Saturday Sled & Metabolic Conditioning Circuit [DEMO]',
                    'category' => 'Metabolic Conditioning',
                    'instructor_id' => $coachOnly->id,
                    'day_of_week' => 6,
                    'starts_at_time' => '17:30:00',
                    'ends_at_time' => '18:30:00',
                    'session_date' => now()->addDays(5)->toDateString(),
                    'room_or_zone' => 'Turf & Conditioning Lane',
                    'capacity' => 16,
                    'booked_count' => 0,
                    'requires_active_membership' => true,
                    'summary' => 'Low-impact work capacity conditioning using prowler sleds, carries, and erg intervals.',
                ],
                [
                    'slug' => 'sunday-thoracic-hip-mobility-session-demo',
                    'title' => 'Sunday Thoracic & Hip Mobility Recovery Session [DEMO]',
                    'category' => 'Mobility & Core',
                    'instructor_id' => $coachOnly->id,
                    'day_of_week' => 7,
                    'starts_at_time' => '11:00:00',
                    'ends_at_time' => '12:00:00',
                    'session_date' => now()->addDays(6)->toDateString(),
                    'room_or_zone' => 'Recovery & Stretching Zone',
                    'capacity' => 15,
                    'booked_count' => 0,
                    'requires_active_membership' => true,
                    'summary' => 'Guided joint decompression, overhead shoulder mobility, and core bracing reset.',
                ],
            ];

            foreach ($gymClasses as $cls) {
                $createdClass = GymClass::updateOrCreate(
                    ['slug' => $cls['slug']],
                    array_merge($cls, ['status' => 'scheduled', 'is_demo' => true])
                );

                if ($cls['slug'] === 'barbell-squat-deadlift-clinic-demo') {
                    GymClassBooking::updateOrCreate(
                        ['gym_class_id' => $createdClass->id, 'user_id' => $customer->id],
                        [
                            'gym_membership_id' => $demoMembership->id,
                            'status' => 'booked',
                            'booked_at' => now()->subDay(),
                        ]
                    );
                }
            }

            GymHoliday::updateOrCreate(
                ['holiday_date' => now()->addDays(25)->toDateString()],
                [
                    'title' => 'Scheduled Equipment Calibration & Floor Maintenance [DEMO]',
                    'is_closed' => false,
                    'special_opens_at' => '10:00:00',
                    'special_closes_at' => '21:00:00',
                    'note' => 'Morning barbell calibration 06:00–10:00; open 10:00–21:00.',
                    'is_demo' => true,
                ]
            );

            GymHoliday::updateOrCreate(
                ['holiday_date' => now()->addDays(45)->toDateString()],
                [
                    'title' => 'Gazetted Public Holiday Schedule [DEMO]',
                    'is_closed' => true,
                    'note' => 'Facility closed for national holiday; normal hours resume next morning at 06:00.',
                    'is_demo' => true,
                ]
            );

            Enquiry::updateOrCreate(
                ['phone' => '0321-0000111', 'type' => 'gym_trial'],
                [
                    'name' => 'Demo Local Trial Visitor',
                    'email' => 'trial.visitor@binmalikfitness.test',
                    'city' => 'Lahore (Demo)',
                    'preferred_trial_date' => now()->addDays(2)->toDateString(),
                    'preferred_time_window' => 'Evening (18:00 – 20:00)',
                    'interested_membership_plan_id' => $quarterlyPlan->id,
                    'message' => 'Would like to inspect the squat racks and book a 1-day trial session before joining the quarterly plan.',
                    'status' => 'trial_scheduled',
                    'assigned_to' => $manager->id,
                    'staff_notes' => 'Confirmed trial slot for 18:30 PKT with floor tour.',
                    'ip_address' => '127.0.0.1',
                ]
            );
        }

        // 15. Stage 6 Complete Dashboards & Business Operations Seed Data
        $demoOrderId = Order::where('order_number', 'BMF-DEMO-1001')->value('id');

        $demoTicket = SupportTicket::updateOrCreate(
            ['ticket_number' => 'TKT-DEMO-1001'],
            [
                'user_id' => $customer->id,
                'assigned_to' => $manager->id,
                'order_id' => $demoOrderId,
                'category' => 'coaching',
                'priority' => 'high',
                'status' => SupportTicket::STATUS_AWAITING_CUSTOMER,
                'subject' => 'Sehri & Post-Taraweeh Macro Timing Adjustment Request [DEMO]',
                'last_reply_at' => now()->subHours(2),
                'is_demo' => true,
            ]
        );

        SupportTicketMessage::updateOrCreate(
            [
                'support_ticket_id' => $demoTicket->id,
                'sender_id' => $customer->id,
                'is_internal_note' => false,
            ],
            [
                'message' => 'Assalam-o-Alaikum Coach, can I shift 40g of my evening complex carbs to my post-Taraweeh meal on heavy lower-body training days?',
            ]
        );

        SupportTicketMessage::updateOrCreate(
            [
                'support_ticket_id' => $demoTicket->id,
                'sender_id' => $manager->id,
                'is_internal_note' => false,
            ],
            [
                'message' => 'Walaikum Assalam! Yes — shifting 40g carbs (1 medium whole-wheat chapati or 150g boiled rice) to post-Taraweeh on Lower Strength days is approved and updated in your active plan.',
            ]
        );

        SupportTicketMessage::updateOrCreate(
            [
                'support_ticket_id' => $demoTicket->id,
                'sender_id' => $manager->id,
                'is_internal_note' => true,
            ],
            [
                'message' => 'INTERNAL STAFF NOTE: Verified client compliance is 95% on weekly check-ins. Keep sodium intake steady.',
            ]
        );

        $cmsPages = [
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions of Service [DEMO]',
                'section' => 'policy',
                'excerpt' => 'Governing terms for online coaching, digital diet plans, supplement ecommerce, and physical gym memberships in Pakistan.',
                'body' => 'All coaching programs, digital nutrition guides, and supplement orders are governed by transparent PKR pricing, verified authenticity documentation, and strict medical disclaimer boundaries.',
                'status' => 'published',
            ],
            [
                'slug' => 'privacy-and-health-data-policy',
                'title' => 'Privacy & Confidential Health Data Policy [DEMO]',
                'section' => 'policy',
                'excerpt' => 'How BIN MALIK FITNESS protects PAR-Q health intakes, progress photos, and payment receipts.',
                'body' => 'Confidential health intakes and progress photos are stored on non-public encrypted disks and restricted strictly to the account owner, assigned coach, and platform administrator with full audit logging.',
                'status' => 'published',
            ],
            [
                'slug' => 'refund-return-and-shipping-policy',
                'title' => 'Supplement Return, Seal Integrity & Nationwide Shipping Policy [DEMO]',
                'section' => 'policy',
                'excerpt' => 'Nationwide courier dispatch, COD verification thresholds, and unopened tamper-evident seal return rules.',
                'body' => 'Opened consumable supplements cannot be returned for safety and hygiene compliance unless a verified batch defect is documented.',
                'status' => 'published',
            ],
            [
                'slug' => 'pakistani-whole-food-protein-guide',
                'title' => 'Pakistani Whole-Food Protein & Mandi Macro Guide [DEMO]',
                'section' => 'editorial',
                'excerpt' => 'Practical protein comparisons across chicken breast, beef qeema, eggs, fish, daal, and verified whey isolate.',
                'body' => 'Editorial reference for coaching and diet-plan clients balancing household dastarkhwan meals with high-protein targets.',
                'status' => 'published',
            ],
        ];

        foreach ($cmsPages as $pageData) {
            CmsPage::updateOrCreate(
                ['slug' => $pageData['slug']],
                array_merge($pageData, [
                    'published_at' => now()->subDays(10),
                    'updated_by' => $admin->id,
                    'is_demo' => true,
                ])
            );
        }

        $mediaAssets = [
            [
                'title' => 'Athletic Barbell Training Hero Photograph [DEMO]',
                'collection' => 'hero',
                'path' => '/images/demo/hero-training.png',
                'alt_text' => 'Athlete performing heavy barbell compound training in dark charcoal and gold studio lighting',
                'size_bytes' => 428500,
            ],
            [
                'title' => '1-on-1 Online Coaching Assessment Session [DEMO]',
                'collection' => 'coaching',
                'path' => '/images/demo/coach-portrait.png',
                'alt_text' => 'Strength and conditioning coach reviewing client training log and biomechanics',
                'size_bytes' => 385200,
            ],
            [
                'title' => 'Authentic Whey Isolate 5lb Studio Packshot [DEMO]',
                'collection' => 'shop',
                'path' => '/images/demo/supplement-whey.png',
                'alt_text' => 'Tamper-sealed matte black and gold whey protein isolate tub on warm ivory surface',
                'size_bytes' => 312400,
            ],
            [
                'title' => 'Physical Gym Competition Power Rack Zone [DEMO]',
                'collection' => 'gym',
                'path' => '/images/demo/gym-interior.png',
                'alt_text' => 'BIN MALIK FITNESS physical gym floor featuring calibrated plates and competition racks',
                'size_bytes' => 461900,
            ],
        ];

        foreach ($mediaAssets as $assetData) {
            MediaAsset::updateOrCreate(
                ['path' => $assetData['path'], 'title' => $assetData['title']],
                array_merge($assetData, [
                    'disk' => 'public',
                    'mime_type' => 'image/png',
                    'uploaded_by' => $admin->id,
                    'is_demo' => true,
                ])
            );
        }

        $testimonialConsent = ConsentRecord::updateOrCreate(
            ['user_id' => $customer->id, 'type' => 'public_testimonial'],
            [
                'consent_type' => 'public_testimonial_display',
                'version' => 'v1.0',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DemoCatalogSeeder/1.0',
                'granted_at' => now()->subDays(20),
                'withdrawn_at' => null,
            ]
        );

        Testimonial::updateOrCreate(
            ['author_label' => 'Demo Coaching Client #104 (Identity Protected)'],
            [
                'display_name' => 'Demo Coaching Client #104 (Identity Protected)',
                'context_label' => '12-Week Transformation Coaching [DEMO]',
                'program_context' => '12-Week Transformation Coaching [DEMO]',
                'quote' => 'Structured Pakistani home-cooked meal macros and weekly barbell form feedback helped me drop waist circumference steadily without cutting out family dinners. [DEMO SAMPLE — NOT A REAL TESTIMONIAL]',
                'rating' => 5,
                'consent_record_id' => $testimonialConsent->id,
                'verification_note' => 'Verified via ConsentRecord #'.$testimonialConsent->id,
                'status' => 'published',
                'is_demo' => true,
            ]
        );

        $firstProduct = Product::first();
        if ($firstProduct) {
            SeoMetadata::updateOrCreate(
                ['seoable_type' => Product::class, 'seoable_id' => $firstProduct->id],
                [
                    'title' => "{$firstProduct->name} — Verified Batch & COA | BIN MALIK FITNESS",
                    'meta_title' => "{$firstProduct->name} — Verified Batch & COA | BIN MALIK FITNESS",
                    'description' => 'Authentic imported & DRAP-referenced gym nutrition in Pakistan with batch verification, full nutrition facts, and nationwide PKR delivery.',
                    'meta_description' => 'Authentic imported & DRAP-referenced gym nutrition in Pakistan with batch verification, full nutrition facts, and nationwide PKR delivery.',
                    'canonical_url' => 'https://binmalikfitness.com/products/'.$firstProduct->slug,
                    'og_image' => '/images/demo/supplement-whey.png',
                    'robots' => 'index,follow',
                ]
            );
        }

        $notificationTemplates = [
            [
                'key' => 'order.payment_verified',
                'name' => 'Order Payment Verified & Fulfilment Triggered',
                'channel' => 'email',
                'subject' => 'Payment Verified for Order {{order_number}} — BIN MALIK FITNESS',
                'body_template' => 'Assalam-o-Alaikum {{customer_name}}, your payment of {{amount_pkr}} for Order {{order_number}} has been verified by our operations desk.',
            ],
            [
                'key' => 'coaching.checkin_reviewed',
                'name' => 'Weekly Coaching Check-In Feedback Ready',
                'channel' => 'database',
                'subject' => 'Coach Feedback Posted for Your Weekly Check-In',
                'body_template' => 'Your coach {{coach_name}} has reviewed your {{check_in_date}} check-in and updated your macro/training notes.',
            ],
            [
                'key' => 'gym.membership_expiring',
                'name' => 'Gym Membership 7-Day Renewal Reminder',
                'channel' => 'whatsapp_consent',
                'subject' => 'Your Gym Membership ({{membership_number}}) Renews in 7 Days',
                'body_template' => 'Reminder: Your {{plan_name}} membership ({{membership_number}}) expires on {{ends_at}}. Renew online or at reception.',
            ],
            [
                'key' => 'support.ticket_replied',
                'name' => 'Support Ticket Staff Reply Alert',
                'channel' => 'database',
                'subject' => 'New Reply on Support Ticket {{ticket_number}}',
                'body_template' => 'Our support team has responded to your ticket {{ticket_number}} ({{subject}}). View the response in your Customer Portal.',
            ],
        ];

        foreach ($notificationTemplates as $tpl) {
            NotificationTemplate::updateOrCreate(
                ['key' => $tpl['key']],
                array_merge($tpl, ['is_active' => true, 'is_demo' => true])
            );
        }

        RoleNotification::updateOrCreate(
            ['user_id' => $customer->id, 'type' => 'coaching.checkin_reviewed'],
            [
                'role_scope' => 'customer',
                'title' => 'Weekly Check-In Reviewed by Coach',
                'body' => 'Your latest biometric check-in has been reviewed. Keep daily hydration at 3.8L and maintain 2,150 kcal.',
                'action_url' => '/account#check-ins',
                'read_at' => null,
            ]
        );

        RoleNotification::updateOrCreate(
            ['user_id' => $manager->id, 'type' => 'operations.daily_digest'],
            [
                'role_scope' => 'manager',
                'title' => 'Today’s Operational Queue Ready',
                'body' => 'Review pending bank transfer proofs, scheduled gym trial visitors, and assigned coaching check-ins.',
                'action_url' => '/manage/manager',
                'read_at' => null,
            ]
        );

        PrivacyRequest::updateOrCreate(
            ['user_id' => $customer->id, 'request_type' => PrivacyRequest::TYPE_DATA_EXPORT],
            [
                'status' => PrivacyRequest::STATUS_PENDING,
                'customer_notes' => 'Requesting a portable copy of my training logs, check-in history, and consent audit trail [DEMO].',
            ]
        );
    }
}
