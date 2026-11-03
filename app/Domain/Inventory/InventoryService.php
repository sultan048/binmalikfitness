<?php

namespace App\Domain\Inventory;

use App\Domain\Platform\AuditLogger;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Atomically reserves stock using a conditional SQL UPDATE to prevent overselling under concurrency.
     */
    public function reserve(
        ProductVariant $variant,
        int $quantity,
        string $referenceType,
        int $referenceId,
        ?User $actor = null
    ): InventoryMovement {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => ['Reservation quantity must be at least 1.']]);
        }

        return DB::transaction(function () use ($variant, $quantity, $referenceType, $referenceId, $actor) {
            $affected = DB::table('product_variants')
                ->where('id', $variant->id)
                ->where('is_active', true)
                ->whereRaw('(stock_on_hand - stock_reserved) >= ?', [$quantity])
                ->update([
                    'stock_reserved' => DB::raw('stock_reserved + '.(int) $quantity),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'stock' => ["Insufficient available stock for SKU {$variant->sku}. Please reduce quantity."],
                ]);
            }

            $fresh = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            return InventoryMovement::create([
                'product_variant_id' => $fresh->id,
                'inventory_batch_id' => null,
                'type' => 'reserve',
                'quantity_delta' => 0,
                'on_hand_after' => $fresh->stock_on_hand,
                'reserved_after' => $fresh->stock_reserved,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => "Reserved {$quantity} unit(s) for {$referenceType} #{$referenceId}",
                'actor_id' => $actor?->id,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Atomically releases previously reserved stock (e.g., when an order is cancelled before shipment).
     */
    public function release(
        ProductVariant $variant,
        int $quantity,
        string $referenceType,
        int $referenceId,
        ?User $actor = null,
        string $reason = 'Order cancelled'
    ): InventoryMovement {
        return DB::transaction(function () use ($variant, $quantity, $referenceType, $referenceId, $actor, $reason) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $newReserved = max(0, $locked->stock_reserved - $quantity);

            $locked->update([
                'stock_reserved' => $newReserved,
            ]);

            $movement = InventoryMovement::create([
                'product_variant_id' => $locked->id,
                'inventory_batch_id' => null,
                'type' => 'release',
                'quantity_delta' => 0,
                'on_hand_after' => $locked->stock_on_hand,
                'reserved_after' => $newReserved,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'actor_id' => $actor?->id,
                'created_at' => now(),
            ]);

            $this->notifyStockAlertsIfAvailable($locked->fresh());

            return $movement;
        });
    }

    /**
     * Commits reserved stock on shipment, deducting from both stock_on_hand and stock_reserved
     * and allocating from the earliest-expiring active batch (FEFO).
     */
    public function commit(
        ProductVariant $variant,
        int $quantity,
        string $referenceType,
        int $referenceId,
        ?User $actor = null
    ): InventoryMovement {
        return DB::transaction(function () use ($variant, $quantity, $referenceType, $referenceId, $actor) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            $newOnHand = max(0, $locked->stock_on_hand - $quantity);
            $newReserved = max(0, $locked->stock_reserved - $quantity);

            $locked->update([
                'stock_on_hand' => $newOnHand,
                'stock_reserved' => $newReserved,
            ]);

            // Deduct from earliest expiring batch (FEFO) if batch tracking exists
            $remainingToDeduct = $quantity;
            $primaryBatchId = null;

            $batches = InventoryBatch::query()
                ->where('product_variant_id', $locked->id)
                ->where('quantity_remaining', '>', 0)
                ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END, expires_at ASC, id ASC')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($remainingToDeduct <= 0) {
                    break;
                }
                $deduct = min($batch->quantity_remaining, $remainingToDeduct);
                $batch->update([
                    'quantity_remaining' => $batch->quantity_remaining - $deduct,
                ]);
                if ($primaryBatchId === null) {
                    $primaryBatchId = $batch->id;
                }
                $remainingToDeduct -= $deduct;
            }

            return InventoryMovement::create([
                'product_variant_id' => $locked->id,
                'inventory_batch_id' => $primaryBatchId,
                'type' => 'commit',
                'quantity_delta' => -$quantity,
                'on_hand_after' => $newOnHand,
                'reserved_after' => $newReserved,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => "Shipped {$quantity} unit(s) for {$referenceType} #{$referenceId}",
                'actor_id' => $actor?->id,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Receives a new inventory batch/lot and increments on-hand stock.
     */
    public function receiveBatch(ProductVariant $variant, array $data, User $actor): InventoryBatch
    {
        return DB::transaction(function () use ($variant, $data, $actor) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $qty = (int) $data['quantity_received'];

            if ($qty <= 0) {
                throw ValidationException::withMessages(['quantity_received' => ['Batch quantity must be greater than zero.']]);
            }

            $batch = InventoryBatch::create([
                'product_variant_id' => $locked->id,
                'batch_number' => $data['batch_number'],
                'manufactured_at' => $data['manufactured_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'quantity_received' => $qty,
                'quantity_remaining' => $qty,
                'unit_cost_paisa' => $data['unit_cost_paisa'] ?? $locked->cost_price,
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'received_by' => $actor->id,
                'is_demo' => (bool) ($data['is_demo'] ?? false),
            ]);

            $newOnHand = $locked->stock_on_hand + $qty;
            $locked->update([
                'stock_on_hand' => $newOnHand,
                'sample_lot_number' => $data['batch_number'],
                'sample_expiry_date' => $data['expires_at'] ?? $locked->sample_expiry_date,
            ]);

            InventoryMovement::create([
                'product_variant_id' => $locked->id,
                'inventory_batch_id' => $batch->id,
                'type' => 'receive',
                'quantity_delta' => $qty,
                'on_hand_after' => $newOnHand,
                'reserved_after' => $locked->stock_reserved,
                'reference_type' => 'batch',
                'reference_id' => $batch->id,
                'reason' => "Received batch {$batch->batch_number} (+{$qty})",
                'actor_id' => $actor->id,
                'created_at' => now(),
            ]);

            $this->audit->record(
                'inventory.batch.received',
                $batch,
                null,
                [
                    'sku' => $locked->sku,
                    'batch_number' => $batch->batch_number,
                    'quantity_received' => $qty,
                    'expires_at' => $batch->expires_at?->toDateString(),
                    'on_hand_after' => $newOnHand,
                ],
                $actor
            );

            $this->notifyStockAlertsIfAvailable($locked->fresh());

            return $batch;
        });
    }

    /**
     * Manual staff stock adjustment with audit logging.
     */
    public function adjustStock(ProductVariant $variant, int $delta, string $reason, User $actor): InventoryMovement
    {
        if ($delta === 0) {
            throw ValidationException::withMessages(['delta' => ['Stock adjustment delta cannot be zero.']]);
        }

        return DB::transaction(function () use ($variant, $delta, $reason, $actor) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $beforeOnHand = $locked->stock_on_hand;
            $newOnHand = $beforeOnHand + $delta;

            if ($newOnHand < $locked->stock_reserved) {
                throw ValidationException::withMessages([
                    'delta' => ["Cannot reduce stock on hand ({$newOnHand}) below currently reserved stock ({$locked->stock_reserved})."],
                ]);
            }

            $locked->update([
                'stock_on_hand' => $newOnHand,
            ]);

            $movement = InventoryMovement::create([
                'product_variant_id' => $locked->id,
                'inventory_batch_id' => null,
                'type' => 'adjustment',
                'quantity_delta' => $delta,
                'on_hand_after' => $newOnHand,
                'reserved_after' => $locked->stock_reserved,
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'reason' => $reason,
                'actor_id' => $actor->id,
                'created_at' => now(),
            ]);

            $this->audit->record(
                'inventory.stock.adjusted',
                $locked,
                ['stock_on_hand' => $beforeOnHand],
                ['stock_on_hand' => $newOnHand, 'delta' => $delta, 'reason' => $reason],
                $actor
            );

            if ($delta > 0) {
                $this->notifyStockAlertsIfAvailable($locked->fresh());
            }

            return $movement;
        });
    }

    public function notifyStockAlertsIfAvailable(ProductVariant $variant): int
    {
        if ($variant->availableStock() <= 0) {
            return 0;
        }

        return StockAlert::query()
            ->where('product_variant_id', $variant->id)
            ->where('status', 'subscribed')
            ->update([
                'status' => 'notified',
                'notified_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
