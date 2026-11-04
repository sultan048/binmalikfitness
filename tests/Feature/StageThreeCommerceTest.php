<?php

namespace Tests\Feature;

use App\Domain\Inventory\InventoryService;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\InventoryBatch;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\StockAlert;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StageThreeCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_shop_search_category_brand_price_stock_filters_and_sorting(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('[DEMO] Whey Protein Isolate')
            ->assertSee('[DEMO] Micronized Creatine Monohydrate');

        // Category filter
        $this->get('/shop?category=creatine')
            ->assertOk()
            ->assertSee('[DEMO] Micronized Creatine Monohydrate')
            ->assertDontSee('[DEMO] Matte Black Leak-Proof Training Shaker');

        // Search query
        $this->get('/shop?q=Shaker')
            ->assertOk()
            ->assertSee('[DEMO] Matte Black Leak-Proof Training Shaker')
            ->assertDontSee('[DEMO] Micronized Creatine Monohydrate');

        // Price range filter (Shaker is PKR 1,850; Whey is PKR 24,500)
        $this->get('/shop?min_price=1000&max_price=3000&in_stock=1&sort=price_asc')
            ->assertOk()
            ->assertSee('[DEMO] Matte Black Leak-Proof Training Shaker')
            ->assertDontSee('[DEMO] Whey Protein Isolate');

        // Product detail page tracks recently viewed and renders batches & regulatory docs
        $this->get('/products/demo-whey-protein-isolate-5lb')
            ->assertOk()
            ->assertSee('DEMO-WPI-5LB-CHOC')
            ->assertSee('DEMO-LOT-2026A')
            ->assertSee('Supplement / Nutrition Facts');
    }

    public function test_cart_coupon_validation_and_server_side_pricing_calculations(): void
    {
        $creatine = ProductVariant::where('sku', 'DEMO-CRE-300G-UNF')->firstOrFail(); // PKR 6,800

        // Add 1 unit to cart
        $this->post('/cart/items', [
            'product_variant_id' => $creatine->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.index'));

        // Apply valid coupon BMF10 (10% off orders over PKR 5,000)
        $this->post('/cart/coupon', [
            'coupon_code' => 'bmf10',
        ])->assertRedirect();

        // Subtotal = PKR 6,800, Discount = PKR 680, Net = PKR 6,120 (< 10,000 threshold -> Standard shipping PKR 350)
        // Total = 6,120 + 350 = PKR 6,470
        $this->get('/cart')
            ->assertOk()
            ->assertSee('BMF10')
            ->assertSee('PKR 6,470');

        // Increase quantity to 2 -> Subtotal = PKR 13,600, Discount = PKR 1,360, Net = PKR 12,240 (>= 10,000 -> FREE shipping)
        $cartItem = CartItem::firstOrFail();
        $this->put(route('cart.items.update', $cartItem->id), [
            'quantity' => 2,
        ])->assertRedirect();

        $this->get('/cart')
            ->assertOk()
            ->assertSee('FREE')
            ->assertSee('PKR 12,240');

        // Remove coupon
        $this->delete(route('cart.coupon.remove'))->assertRedirect();
        $this->get('/cart')->assertOk()->assertSee('PKR 13,600');
    }

    public function test_guest_cod_checkout_reserves_stock_and_allows_tracking_and_cancellation(): void
    {
        $shaker = ProductVariant::where('sku', 'DEMO-ACC-SHK700-BLK')->firstOrFail();
        $initialOnHand = $shaker->stock_on_hand; // 40

        $this->post('/cart/items', [
            'product_variant_id' => $shaker->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.index'));

        $idemKey = 'guest-cod-'.Str::uuid();

        $res = $this->post('/checkout', [
            '_idempotency_key' => $idemKey,
            'customer_name' => 'Guest Athlete',
            'customer_email' => 'guest.athlete@example.com',
            'customer_phone' => '0321-7654321',
            'line1' => 'House 88, Street 12, F-10/2',
            'city' => 'Islamabad',
            'province' => 'Islamabad Capital Territory',
            'shipping_method' => 'standard_courier',
            'payment_method' => 'cod',
        ]);

        $order = Order::where('customer_email', 'guest.athlete@example.com')->firstOrFail();
        // The access token never appears in the URL; access is remembered in the session instead
        $res->assertRedirect(route('orders.confirmation', ['orderNumber' => $order->order_number]));

        // Verify atomic stock reservation
        $shaker->refresh();
        $this->assertSame($initialOnHand, $shaker->stock_on_hand);
        $this->assertSame(2, $shaker->stock_reserved);
        $this->assertSame($initialOnHand - 2, $shaker->availableStock());
        $this->assertSame(Order::STATUS_COD_CONFIRMED, $order->status);
        $this->assertSame(Order::PAYMENT_COD_PENDING, $order->payment_status);

        // The guest who placed the order can view confirmation and invoice in the same session
        $this->get(route('orders.confirmation', ['orderNumber' => $order->order_number]))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->get(route('orders.invoice', ['orderNumber' => $order->order_number]))
            ->assertOk()
            ->assertSee('Commercial Order Invoice');

        // A different visitor (fresh session) is refused
        $this->flushSession();
        $this->get(route('orders.confirmation', ['orderNumber' => $order->order_number]))
            ->assertForbidden();

        // A legacy ?token= link is accepted once, then reloaded without the token
        $this->get(route('orders.confirmation', ['orderNumber' => $order->order_number, 'token' => $order->access_token]))
            ->assertRedirect(route('orders.confirmation', ['orderNumber' => $order->order_number]));
        $this->get(route('orders.confirmation', ['orderNumber' => $order->order_number]))
            ->assertOk();
        $this->flushSession();

        // Guest can track order by order_number + phone
        $this->get(route('orders.track', ['order_number' => $order->order_number, 'contact' => '0321-7654321']))
            ->assertOk()
            ->assertSee($order->order_number);

        // Guest cancels order before shipment -> reserved stock is released atomically
        $this->post(route('orders.cancel', $order->order_number), [
            'token' => $order->access_token,
            'reason' => 'Need to change delivery city',
        ])->assertRedirect();

        $shaker->refresh();
        $order->refresh();
        $this->assertSame(0, $shaker->stock_reserved);
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_bank_transfer_proof_upload_never_auto_verifies_and_requires_staff_verification(): void
    {
        Storage::fake('local');

        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $whey = ProductVariant::where('sku', 'DEMO-WPI-5LB-VAN')->firstOrFail();
        $initialOnHand = $whey->stock_on_hand; // 8
        $initialBatchRemaining = InventoryBatch::where('product_variant_id', $whey->id)->firstOrFail()->quantity_remaining;

        $this->actingAs($customer)->post('/cart/items', [
            'product_variant_id' => $whey->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer)->post('/checkout', [
            '_idempotency_key' => 'bt-'.Str::uuid(),
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'saved_address_id' => $customer->addresses()->first()->id,
            'shipping_method' => 'standard_courier',
            'payment_method' => 'bank_transfer',
        ])->assertRedirect();

        $order = Order::where('user_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);

        // Upload bank transfer receipt -> MUST NOT mark order as verified/paid automatically!
        $receipt = UploadedFile::fake()->create('meezan-receipt.pdf', 150, 'application/pdf');
        $this->actingAs($customer)->post(route('orders.payment-proof.store', $order->order_number), [
            'token' => $order->access_token,
            'transaction_reference' => 'MEEZAN-TXN-774411',
            'sender_bank_name' => 'Meezan Bank',
            'sender_account_last4' => '9912',
            'receipt_file' => $receipt,
        ])->assertRedirect();

        $order->refresh();
        $payment = $order->payments()->firstOrFail();
        $this->assertSame(Order::PAYMENT_PROOF_SUBMITTED, $order->payment_status);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertSame('proof_submitted', $payment->status);
        $this->assertNotNull($payment->proof_private_file_id);
        Storage::disk('local')->assertExists($payment->proofFile->path);

        // Manager cannot ship order before payment verification
        $this->actingAs($manager)->put(route('manage.orders.status.update', $order), [
            'status' => Order::STATUS_SHIPPED,
            'tracking_number' => 'TCS-111',
        ])->assertSessionHasErrors('status');

        // Manager verifies payment (idempotent)
        $this->actingAs($manager)->post(route('manage.payments.verify', $payment), [
            'staff_notes' => 'Matched IBFT credit in Meezan statement',
        ])->assertRedirect();

        // Calling verify a second time is idempotent
        $this->actingAs($manager)->post(route('manage.payments.verify', $payment))->assertRedirect();

        $order->refresh();
        $this->assertSame(Order::PAYMENT_VERIFIED, $order->payment_status);
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);

        // Now Manager ships order -> commits stock from on_hand, reserved, and earliest batch (FEFO)
        $this->actingAs($manager)->put(route('manage.orders.status.update', $order), [
            'status' => Order::STATUS_SHIPPED,
            'courier_name' => 'Leopards Courier',
            'tracking_number' => 'LEO-882910',
        ])->assertRedirect();

        $whey->refresh();
        $batchAfter = InventoryBatch::where('product_variant_id', $whey->id)->firstOrFail();
        $this->assertSame($initialOnHand - 1, $whey->stock_on_hand);
        $this->assertSame(0, $whey->stock_reserved);
        $this->assertSame($initialBatchRemaining - 1, $batchAfter->quantity_remaining);

        // Mark delivered
        $this->actingAs($manager)->put(route('manage.orders.status.update', $order), [
            'status' => Order::STATUS_DELIVERED,
        ])->assertRedirect();

        $this->assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);
    }

    public function test_atomic_inventory_prevents_overselling_and_double_click_checkout_is_blocked(): void
    {
        $preWorkout = ProductVariant::where('sku', 'DEMO-PRE-30-CIT')->firstOrFail();
        $preWorkout->update(['stock_on_hand' => 3, 'stock_reserved' => 0]);

        $inventory = app(InventoryService::class);

        // First reservation of 2 units succeeds (1 unit left available)
        $inventory->reserve($preWorkout, 2, 'order', 9001);
        $this->assertSame(1, $preWorkout->fresh()->availableStock());

        // Second concurrent reservation of 2 units must fail atomically without modifying stock_reserved
        try {
            $inventory->reserve($preWorkout->fresh(), 2, 'order', 9002);
            $this->fail('Expected ValidationException when reserving more stock than available.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('stock', $e->errors());
        }

        $this->assertSame(2, $preWorkout->fresh()->stock_reserved);
        $this->assertSame(1, $preWorkout->fresh()->availableStock());

        // Double-click checkout with identical idempotency key is rejected with 409
        $customer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $this->actingAs($customer)->post('/cart/items', [
            'product_variant_id' => $preWorkout->id,
            'quantity' => 1,
        ]);

        $idemKey = 'double-click-'.Str::uuid();
        $payload = [
            '_idempotency_key' => $idemKey,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'saved_address_id' => $customer->addresses()->first()->id,
            'shipping_method' => 'standard_courier',
            'payment_method' => 'cod',
        ];

        $this->actingAs($customer)->post('/checkout', $payload)->assertRedirect();
        $this->actingAs($customer)->post('/checkout', $payload)->assertStatus(409);

        $this->assertSame(1, Order::where('idempotency_key', $idemKey)->count());
    }

    public function test_admin_vs_manager_commerce_authorization_refunds_coupons_and_batches(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $manager = User::where('email', 'manager.demo@binmalikfitness.test')->firstOrFail();
        $creatine = ProductVariant::where('sku', 'DEMO-CRE-300G-UNF')->firstOrFail();

        // Subscribe a stock alert for creatine
        $this->post('/stock-alerts', [
            'product_variant_id' => $creatine->id,
            'email' => 'restock.watcher@example.com',
            'phone' => '0300-9988776',
        ])->assertRedirect();

        $alert = StockAlert::where('email', 'restock.watcher@example.com')->firstOrFail();
        $this->assertSame('subscribed', $alert->status);

        // Manager CAN receive a new batch -> increments stock and notifies stock alert subscribers
        $this->actingAs($manager)->post(route('manage.variants.batches.store', $creatine), [
            'batch_number' => 'LOT-2026-NEW',
            'quantity_received' => 15,
            'expires_at' => now()->addMonths(20)->toDateString(),
            'supplier_reference' => 'SUP-991',
        ])->assertRedirect();

        $this->assertSame('notified', $alert->fresh()->status);

        // Manager CAN create an approved coupon <= 20%, but CANNOT create a 40% coupon
        $this->actingAs($manager)->post(route('manage.coupons.store'), [
            'code' => 'MGR15',
            'discount_type' => 'percentage',
            'discount_value' => 15,
        ])->assertRedirect();
        $this->assertDatabaseHas('coupons', ['code' => 'MGR15']);

        $this->actingAs($manager)->post(route('manage.coupons.store'), [
            'code' => 'MGR40',
            'discount_type' => 'percentage',
            'discount_value' => 40,
        ])->assertSessionHasErrors('discount_value');

        $category = ProductCategory::firstOrFail();

        // Manager CANNOT create catalog products (403) or issue refunds (403)
        $this->actingAs($manager)->post(route('manage.products.store'), [
            'category_id' => $category->id,
            'name' => 'Unauthorized Product',
            'sku' => 'UNAUTH-01',
            'size' => '1 kg',
            'price_pkr' => 5000,
            'stock_on_hand' => 10,
            'regulatory_status' => 'pending_verification',
        ])->assertForbidden();

        $deliveredOrder = Order::where('order_number', 'BMF-DEMO-1001')->firstOrFail();
        $this->actingAs($manager)->post(route('manage.orders.refunds.store', $deliveredOrder), [
            'amount_pkr' => 1000,
            'reason' => 'Manager unauthorized refund attempt',
        ])->assertForbidden();

        // Admin CAN create catalog products and issue refunds
        $this->actingAs($admin)->post(route('manage.products.store'), [
            'category_id' => $category->id,
            'name' => '[DEMO] Hydrolyzed Whey Isolate — 2 kg',
            'sku' => 'DEMO-HYDRO-2KG',
            'size' => '2 kg',
            'flavor' => 'Belgian Chocolate',
            'price_pkr' => 28500,
            'stock_on_hand' => 12,
            'regulatory_status' => 'verified',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('products', ['sku' => 'DEMO-HYDRO-2KG']);

        $this->actingAs($admin)->post(route('manage.orders.refunds.store', $deliveredOrder), [
            'amount_pkr' => 1500,
            'reason' => 'Partial courtesy refund approved by Owner',
            'reference' => 'REF-TEST-01',
        ])->assertRedirect();

        $deliveredOrder->refresh();
        $this->assertSame(Money::fromRupees(1500), $deliveredOrder->refunded_paisa);
        $this->assertSame(Order::PAYMENT_PARTIALLY_REFUNDED, $deliveredOrder->payment_status);
        $this->assertSame(1, Refund::where('order_id', $deliveredOrder->id)->count());
    }

    public function test_verified_purchase_reviews_and_return_requests_lifecycle(): void
    {
        $admin = User::where('email', 'admin.demo@binmalikfitness.test')->firstOrFail();
        $buyer = User::where('email', 'customer.demo@binmalikfitness.test')->firstOrFail();
        $nonBuyer = User::where('email', 'unassigned.customer@binmalikfitness.test')->firstOrFail();

        $creatineProduct = Product::where('slug', 'demo-micronized-creatine-300g')->firstOrFail();
        $deliveredOrder = Order::where('order_number', 'BMF-DEMO-1001')->firstOrFail();

        // Non-buyer is blocked from submitting a review (403)
        $this->actingAs($nonBuyer)->post(route('products.reviews.store', $creatineProduct), [
            'rating' => 5,
            'title' => 'Fake review attempt',
            'body' => 'Never bought this product.',
        ])->assertForbidden();

        // Verified buyer with delivered order can submit review (pending moderation)
        $this->actingAs($buyer)->post(route('products.reviews.store', $creatineProduct), [
            'rating' => 5,
            'title' => 'Clean mixability and verified batch lot',
            'body' => 'Received sealed tub with matching batch number on the invoice.',
        ])->assertRedirect();

        $review = ProductReview::where('product_id', $creatineProduct->id)->where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame('pending', $review->status);

        // Pending review is not shown publicly until moderated
        $this->get('/products/demo-micronized-creatine-300g')
            ->assertOk()
            ->assertDontSee('Clean mixability and verified batch lot');

        // Admin approves review -> now visible publicly
        $this->actingAs($admin)->put(route('manage.reviews.moderate', $review), [
            'status' => 'approved',
        ])->assertRedirect();

        $this->get('/products/demo-micronized-creatine-300g')
            ->assertOk()
            ->assertSee('Clean mixability and verified batch lot');

        // Buyer submits a return request on delivered order, and Admin resolves + restocks
        $variant = ProductVariant::where('sku', 'DEMO-CRE-300G-UNF')->firstOrFail();
        $stockBeforeReturn = $variant->stock_on_hand;

        $this->actingAs($buyer)->post(route('orders.return.store', $deliveredOrder->order_number), [
            'token' => $deliveredOrder->access_token,
            'reason' => 'Unopened tub exchange',
            'details' => 'Factory seal intact, requesting return.',
        ])->assertRedirect();

        $returnReq = OrderReturnRequest::where('order_id', $deliveredOrder->id)->firstOrFail();
        $this->actingAs($admin)->put(route('manage.returns.resolve', $returnReq), [
            'status' => 'received_and_restocked',
            'resolution_notes' => 'Seal verified intact, restocked 1 unit.',
            'restock_items' => '1',
        ])->assertRedirect();

        $this->assertSame($stockBeforeReturn + 1, $variant->fresh()->stock_on_hand);
        $this->assertSame(Order::STATUS_RETURNED, $deliveredOrder->fresh()->status);
    }
}
