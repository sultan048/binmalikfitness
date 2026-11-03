<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('summary', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('country_of_origin', 80)->nullable();
            $table->string('authorised_distributor_note', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('sku', 64)->unique();
            $table->string('subtitle', 190)->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('ingredients')->nullable();
            $table->json('nutrition_facts')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('warnings')->nullable();
            $table->text('allergens')->nullable();
            $table->string('manufacturer', 160)->nullable();
            $table->string('importer', 160)->nullable();
            $table->boolean('batch_tracking_enabled')->default(true);
            $table->string('regulatory_status', 64)->default('pending_verification')->index(); // verified, pending_verification, exempt_accessory
            $table->string('authenticity_note', 255)->nullable();
            $table->string('status', 24)->default('draft')->index(); // draft, active, archived
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_bestseller')->default(false)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('size', 64)->nullable();
            $table->string('flavor', 64)->nullable();
            $table->unsignedSmallInteger('servings')->nullable();
            $table->unsignedBigInteger('price'); // paisa
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->unsignedBigInteger('cost_price')->nullable(); // staff-only
            $table->integer('stock_on_hand')->default(0);
            $table->integer('stock_reserved')->default(0);
            $table->unsignedSmallInteger('low_stock_threshold')->default(5);
            $table->unsignedInteger('weight_grams')->default(500);
            $table->string('sample_lot_number', 64)->nullable();
            $table->date('sample_expiry_date')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->string('alt_text', 190);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('product_categories');
    }
};
