<?php

namespace App\Http\Controllers\Manage;

use App\Domain\Catalog\CatalogManagementService;
use App\Domain\Commerce\OrderFulfilmentService;
use App\Domain\Inventory\InventoryService;
use App\Domain\Payments\PaymentService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommerceManagementController extends Controller
{
    public function __construct(
        private readonly CatalogManagementService $catalog,
        private readonly InventoryService $inventory,
        private readonly OrderFulfilmentService $fulfilment,
        private readonly PaymentService $payments,
    ) {}

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'variant_sku' => ['nullable', 'string', 'max:64', 'unique:product_variants,sku'],
            'subtitle' => ['nullable', 'string', 'max:190'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'size' => ['required', 'string', 'max:64'],
            'flavor' => ['nullable', 'string', 'max:64'],
            'servings' => ['nullable', 'integer', 'min:1'],
            'price_pkr' => ['required', 'numeric', 'min:1'],
            'compare_at_pkr' => ['nullable', 'numeric', 'min:1'],
            'cost_price_pkr' => ['nullable', 'numeric', 'min:0'],
            'stock_on_hand' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'weight_grams' => ['nullable', 'integer', 'min:1'],
            'ingredients' => ['nullable', 'string'],
            'usage_instructions' => ['nullable', 'string'],
            'warnings' => ['nullable', 'string'],
            'allergens' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string', 'max:160'],
            'importer' => ['nullable', 'string', 'max:160'],
            'regulatory_status' => ['required', 'string', 'in:verified,pending_verification,exempt_accessory'],
            'authenticity_note' => ['nullable', 'string', 'max:255'],
            'sample_lot_number' => ['nullable', 'string', 'max:64'],
            'sample_expiry_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:draft,active,archived'],
        ]);

        $product = $this->catalog->createProductWithVariant($validated, $request->user());

        return back()->with('status', "Created product {$product->name} ({$product->sku}).");
    }

    public function storeProductDocument(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'document_type' => ['required', 'string', 'in:importer_invoice,coa_batch_report,regulatory_filing,label_approval'],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'string', 'in:verified,pending_review,expired'],
            'regulatory_status' => ['nullable', 'string', 'in:verified,pending_verification,exempt_accessory'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $this->catalog->addProductDocument($product, $validated, $request->user());

        return back()->with('status', "Recorded regulatory/authenticity document for {$product->name}.");
    }

    public function receiveBatch(Request $request, ProductVariant $variant): RedirectResponse
    {
        if (! $request->user()->can('inventory.receive')) {
            abort(403, 'You are not authorized to receive inventory batches.');
        }

        $validated = $request->validate([
            'batch_number' => ['required', 'string', 'max:64'],
            'quantity_received' => ['required', 'integer', 'min:1', 'max:10000'],
            'manufactured_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'supplier_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $batch = $this->inventory->receiveBatch($variant, $validated, $request->user());

        return back()->with(
            'status',
            "Received batch {$batch->batch_number} (+{$batch->quantity_received} units) for SKU {$variant->sku}."
        );
    }

    public function adjustStock(Request $request, ProductVariant $variant): RedirectResponse
    {
        if (! $request->user()->can('inventory.adjust')) {
            abort(403, 'You are not authorized to adjust inventory.');
        }

        $validated = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0', 'min:-5000', 'max:5000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->inventory->adjustStock($variant, (int) $validated['delta'], $validated['reason'], $request->user());

        return back()->with('status', "Adjusted stock for SKU {$variant->sku} by {$validated['delta']}.");
    }

    public function storeCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:coupons,code'],
            'description' => ['nullable', 'string', 'max:190'],
            'discount_type' => ['required', 'string', 'in:percentage,fixed_amount'],
            'discount_value' => ['required', 'numeric', 'min:1'],
            'min_subtotal_pkr' => ['nullable', 'numeric', 'min:0'],
            'max_discount_pkr' => ['nullable', 'numeric', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $coupon = $this->catalog->createCoupon($validated, $request->user());

        return back()->with('status', "Created promotional coupon {$coupon->code}.");
    }

    public function verifyPayment(Request $request, Payment $payment): RedirectResponse
    {
        if (! $request->user()->can('payments.verify')) {
            abort(403, 'You are not authorized to verify payments.');
        }

        $validated = $request->validate([
            'staff_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->payments->verifyPayment($payment, $request->user(), $validated['staff_notes'] ?? null);

        return back()->with('status', "Payment for Order {$payment->order->order_number} verified and order moved to Processing.");
    }

    public function rejectPayment(Request $request, Payment $payment): RedirectResponse
    {
        if (! $request->user()->can('payments.verify')) {
            abort(403, 'You are not authorized to reject payments.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->payments->rejectPayment($payment, $request->user(), $validated['reason']);

        return back()->with('status', "Rejected payment proof for Order {$payment->order->order_number}.");
    }

    public function updateOrderStatus(Request $request, Order $order): RedirectResponse
    {
        if (! $request->user()->can('orders.fulfil')) {
            abort(403, 'You are not authorized to update order statuses.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:processing,shipped,delivered,cancelled'],
            'courier_name' => ['nullable', 'string', 'max:80'],
            'tracking_number' => ['nullable', 'string', 'max:80'],
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->fulfilment->transitionStatus($order, $validated['status'], $request->user(), $validated);

        return back()->with('status', "Order {$order->order_number} transitioned to {$validated['status']}.");
    }

    public function issueRefund(Request $request, Order $order): RedirectResponse
    {
        if (! $request->user()->can('refunds.approve')) {
            abort(403, 'Only Administrators may issue refunds.');
        }

        $validated = $request->validate([
            'amount_pkr' => ['required', 'numeric', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $amountPaisa = Money::fromRupees((float) $validated['amount_pkr']);
        $refund = $this->payments->issueRefund(
            $order,
            $amountPaisa,
            $validated['reason'],
            $validated['reference'] ?? null,
            $request->user()
        );

        return back()->with(
            'status',
            'Issued refund of '.Money::format($refund->amount_paisa)." for Order {$order->order_number}."
        );
    }

    public function resolveReturn(Request $request, OrderReturnRequest $returnRequest): RedirectResponse
    {
        if (! $request->user()->can('orders.fulfil')) {
            abort(403, 'You are not authorized to resolve return requests.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected,received_and_restocked'],
            'resolution_notes' => ['required', 'string', 'max:255'],
            'restock_items' => ['nullable', 'boolean'],
        ]);

        $this->fulfilment->resolveReturnRequest(
            $returnRequest,
            $validated['status'],
            $validated['resolution_notes'],
            $request->user(),
            $request->boolean('restock_items')
        );

        return back()->with('status', "Updated return request #{$returnRequest->id} to {$validated['status']}.");
    }

    public function moderateReview(Request $request, ProductReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
        ]);

        $this->catalog->moderateReview($review, $validated['status'], $request->user());

        return back()->with('status', "Moderated product review #{$review->id} ({$validated['status']}).");
    }
}
