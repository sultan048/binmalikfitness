<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('seo_title', 190)->nullable()->after('authenticity_note');
            $table->string('seo_description', 255)->nullable()->after('seo_title');
        });

        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('document_type', 48); // importer_invoice, coa_batch_report, regulatory_filing, label_approval
            $table->string('reference_number', 80)->nullable();
            $table->string('status', 32)->default('pending_review')->index(); // verified, pending_review, expired
            $table->foreignId('private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('batch_number', 64)->index();
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->integer('quantity_received');
            $table->integer('quantity_remaining');
            $table->unsignedBigInteger('unit_cost_paisa')->nullable();
            $table->string('supplier_reference', 120)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->string('type', 32)->index(); // receive, reserve, release, commit, adjustment, return_restock
            $table->integer('quantity_delta');
            $table->integer('on_hand_after');
            $table->integer('reserved_after');
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 190);
            $table->string('phone', 32)->nullable();
            $table->string('status', 24)->default('subscribed')->index(); // subscribed, notified
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'email']);
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_token', 100)->nullable()->index();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description', 190)->nullable();
            $table->string('discount_type', 24); // percentage, fixed_amount
            $table->unsignedBigInteger('discount_value'); // percentage int (e.g. 10) or paisa
            $table->unsignedBigInteger('min_subtotal_paisa')->default(0);
            $table->unsignedBigInteger('max_discount_paisa')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedSmallInteger('per_user_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token', 100)->nullable()->index();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('shipping_method', 32)->default('standard_courier');
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();

            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->string('access_token', 64)->unique();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 120);
            $table->string('customer_email', 190)->index();
            $table->string('customer_phone', 32)->index();
            $table->json('shipping_address_snapshot');
            $table->string('shipping_method', 40)->default('standard_courier'); // standard_courier, express_city
            $table->string('payment_method', 32); // cod, bank_transfer, online_gateway
            $table->string('status', 32)->index(); // pending, awaiting_payment, cod_confirmed, processing, shipped, delivered, cancelled, returned, refunded, failed
            $table->string('payment_status', 32)->index(); // unpaid, cod_pending, proof_submitted, verified, failed, partially_refunded, refunded
            $table->string('currency', 8)->default('PKR');
            $table->unsignedBigInteger('subtotal_paisa');
            $table->unsignedBigInteger('discount_paisa')->default(0);
            $table->unsignedBigInteger('shipping_paisa')->default(0);
            $table->unsignedBigInteger('tax_paisa')->default(0);
            $table->unsignedBigInteger('total_paisa');
            $table->unsignedBigInteger('refunded_paisa')->default(0);
            $table->string('coupon_code_snapshot', 40)->nullable();
            $table->string('courier_name', 80)->nullable();
            $table->string('tracking_number', 80)->nullable();
            $table->text('customer_notes')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name_snapshot', 180);
            $table->string('sku_snapshot', 64);
            $table->string('variant_label_snapshot', 120)->nullable();
            $table->string('batch_number_snapshot', 64)->nullable();
            $table->date('expiry_date_snapshot')->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('unit_price_paisa');
            $table->unsignedBigInteger('line_total_paisa');
            $table->unsignedInteger('weight_grams_snapshot')->default(500);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('method', 32); // cod, bank_transfer, online_gateway
            $table->string('status', 32)->index(); // pending, proof_submitted, verified, rejected, refunded
            $table->unsignedBigInteger('amount_paisa');
            $table->string('currency', 8)->default('PKR');
            $table->string('transaction_reference', 120)->nullable()->index();
            $table->string('sender_bank_name', 120)->nullable();
            $table->string('sender_account_last4', 16)->nullable();
            $table->foreignId('proof_private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('staff_notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unsignedBigInteger('amount_paisa');
            $table->string('reason', 255);
            $table->string('reference', 100)->nullable();
            $table->foreignId('processed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('order_return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 120);
            $table->text('details');
            $table->string('status', 32)->default('requested')->index(); // requested, approved, rejected, received_and_restocked
            $table->string('resolution_notes', 255)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_id', 120)->unique();
            $table->string('event_type', 80);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('status', 32)->default('received')->index();
            $table->timestamps();
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 140);
            $table->text('body');
            $table->boolean('is_verified_purchase')->default(true);
            $table->string('status', 24)->default('pending')->index(); // pending, approved, rejected
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'user_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('payment_webhook_logs');
        Schema::dropIfExists('order_return_requests');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('product_documents');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description']);
        });
    }
};
