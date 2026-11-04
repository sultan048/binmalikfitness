<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the /shop listing: active products ordered by bestseller/featured,
 * and the default-variant / price lookups used for sorting and price filters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'is_bestseller', 'is_featured'], 'products_shop_listing_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['product_id', 'is_default'], 'product_variants_default_index');
            $table->index(['product_id', 'price'], 'product_variants_price_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'placed_at'], 'orders_status_placed_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_placed_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('product_variants_default_index');
            $table->dropIndex('product_variants_price_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_shop_listing_index');
        });
    }
};
