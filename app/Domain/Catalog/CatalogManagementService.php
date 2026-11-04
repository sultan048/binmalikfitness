<?php

namespace App\Domain\Catalog;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Platform\AuditLogger;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogManagementService
{
    public const MANAGER_MAX_PERCENT_COUPON = 20;

    public const MANAGER_MAX_FIXED_COUPON_PAISA = 300000; // PKR 3,000

    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function createProductWithVariant(array $data, User $actor): Product
    {
        if (! $actor->can('pricing.manage')) {
            abort(403, 'You do not have permission to create catalog products or set product pricing.');
        }

        return DB::transaction(function () use ($data, $actor) {
            $slug = Str::slug($data['slug'] ?? $data['name']);
            if (Product::withTrashed()->where('slug', $slug)->exists()) {
                $slug .= '-'.strtolower(Str::random(4));
            }

            $product = Product::create([
                'category_id' => (int) $data['category_id'],
                'brand_id' => ! empty($data['brand_id']) ? (int) $data['brand_id'] : null,
                'name' => $data['name'],
                'slug' => $slug,
                'sku' => strtoupper(trim($data['sku'])),
                'subtitle' => $data['subtitle'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'ingredients' => ! empty($data['ingredients'])
                    ? (is_array($data['ingredients']) ? $data['ingredients'] : array_map('trim', explode(',', $data['ingredients'])))
                    : null,
                'nutrition_facts' => $data['nutrition_facts'] ?? null,
                'usage_instructions' => $data['usage_instructions'] ?? null,
                'warnings' => $data['warnings'] ?? null,
                'allergens' => $data['allergens'] ?? null,
                'manufacturer' => $data['manufacturer'] ?? null,
                'importer' => $data['importer'] ?? null,
                'batch_tracking_enabled' => (bool) ($data['batch_tracking_enabled'] ?? true),
                'regulatory_status' => $data['regulatory_status'] ?? 'pending_verification',
                'authenticity_note' => $data['authenticity_note'] ?? null,
                'seo_title' => $data['seo_title'] ?? $data['name'],
                'seo_description' => $data['seo_description'] ?? ($data['short_description'] ?? null),
                'status' => $data['status'] ?? 'active',
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'is_bestseller' => (bool) ($data['is_bestseller'] ?? false),
                'is_demo' => (bool) ($data['is_demo'] ?? $actor->is_demo),
            ]);

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $data['image_path'] ?? '/images/demo/product-whey-800.webp',
                'alt_text' => $product->name,
                'sort_order' => 1,
                'is_primary' => true,
            ]);

            $pricePaisa = isset($data['price_pkr'])
                ? Money::fromRupees((float) $data['price_pkr'])
                : (int) ($data['price_paisa'] ?? 0);

            $comparePaisa = ! empty($data['compare_at_pkr'])
                ? Money::fromRupees((float) $data['compare_at_pkr'])
                : null;

            $costPaisa = ! empty($data['cost_price_pkr'])
                ? Money::fromRupees((float) $data['cost_price_pkr'])
                : (int) round($pricePaisa * 0.70);

            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => strtoupper(trim($data['variant_sku'] ?? ($data['sku'].'-STD'))),
                'size' => $data['size'] ?? 'Standard',
                'flavor' => $data['flavor'] ?? 'Unflavored',
                'servings' => ! empty($data['servings']) ? (int) $data['servings'] : null,
                'price' => $pricePaisa,
                'compare_at_price' => $comparePaisa,
                'cost_price' => $costPaisa,
                'stock_on_hand' => (int) ($data['stock_on_hand'] ?? 0),
                'stock_reserved' => 0,
                'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 5),
                'weight_grams' => (int) ($data['weight_grams'] ?? 500),
                'sample_lot_number' => $data['sample_lot_number'] ?? null,
                'sample_expiry_date' => $data['sample_expiry_date'] ?? null,
                'is_default' => true,
                'is_active' => true,
            ]);

            $this->audit->record(
                'catalog.product.created',
                $product,
                null,
                ['name' => $product->name, 'sku' => $product->sku, 'price_paisa' => $pricePaisa],
                $actor
            );

            return $product->load(['variants', 'primaryImage']);
        });
    }

    public function addProductDocument(Product $product, array $data, User $actor): ProductDocument
    {
        if (! $actor->can('catalog.manage')) {
            abort(403, 'Only authorized Administrators can manage regulatory documentation.');
        }

        $doc = ProductDocument::create([
            'product_id' => $product->id,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'reference_number' => $data['reference_number'] ?? null,
            'status' => $data['status'] ?? 'verified',
            'issued_at' => $data['issued_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_demo' => (bool) ($data['is_demo'] ?? $actor->is_demo),
        ]);

        if (! empty($data['regulatory_status'])) {
            $product->update(['regulatory_status' => $data['regulatory_status']]);
        }

        $this->audit->record(
            'catalog.document.recorded',
            $doc,
            null,
            ['product_id' => $product->id, 'title' => $doc->title, 'status' => $doc->status],
            $actor
        );

        return $doc;
    }

    public function createCoupon(array $data, User $actor): Coupon
    {
        if (! $actor->can('coupons.manage')) {
            abort(403, 'You do not have permission to manage coupons.');
        }

        $type = $data['discount_type'];
        $rawValue = (float) $data['discount_value'];
        $discountValue = $type === 'percentage'
            ? (int) round($rawValue)
            : Money::fromRupees($rawValue);

        // Enforce Manager promotional cap if actor is not an Admin
        if (! $actor->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            if ($type === 'percentage' && $discountValue > self::MANAGER_MAX_PERCENT_COUPON) {
                throw ValidationException::withMessages([
                    'discount_value' => ['Managers may only create approved promotional coupons up to 20% off.'],
                ]);
            }
            if ($type === 'fixed_amount' && $discountValue > self::MANAGER_MAX_FIXED_COUPON_PAISA) {
                throw ValidationException::withMessages([
                    'discount_value' => ['Managers may only create fixed promotional coupons up to PKR 3,000.'],
                ]);
            }
        }

        $coupon = Coupon::create([
            'code' => strtoupper(trim($data['code'])),
            'description' => $data['description'] ?? null,
            'discount_type' => $type,
            'discount_value' => $discountValue,
            'min_subtotal_paisa' => ! empty($data['min_subtotal_pkr'])
                ? Money::fromRupees((float) $data['min_subtotal_pkr'])
                : 0,
            'max_discount_paisa' => ! empty($data['max_discount_pkr'])
                ? Money::fromRupees((float) $data['max_discount_pkr'])
                : null,
            'usage_limit' => ! empty($data['usage_limit']) ? (int) $data['usage_limit'] : null,
            'per_user_limit' => (int) ($data['per_user_limit'] ?? 1),
            'starts_at' => $data['starts_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => true,
            'created_by' => $actor->id,
            'is_demo' => (bool) $actor->is_demo,
        ]);

        $this->audit->record(
            'commerce.coupon.created',
            $coupon,
            null,
            ['code' => $coupon->code, 'discount_type' => $coupon->discount_type, 'discount_value' => $coupon->discount_value],
            $actor
        );

        return $coupon;
    }

    public function moderateReview(ProductReview $review, string $status, User $actor): ProductReview
    {
        if (! $actor->can('reviews.moderate')) {
            abort(403, 'You do not have permission to moderate product reviews.');
        }

        $previous = $review->status;
        $review->update([
            'status' => $status,
            'moderated_by' => $actor->id,
            'moderated_at' => now(),
        ]);

        $this->audit->record(
            'commerce.review.moderated',
            $review,
            ['status' => $previous],
            ['status' => $status],
            $actor
        );

        return $review->fresh();
    }
}
