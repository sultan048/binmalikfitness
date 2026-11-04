<?php

namespace App\Domain\Commerce;

use App\Domain\Inventory\InventoryService;
use App\Domain\Platform\AuditLogger;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\User;
use App\Notifications\OrderUpdateNotification;
use App\Support\OrderNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderFulfilmentService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    public function transitionStatus(Order $order, string $nextStatus, User $staff, array $meta = []): Order
    {
        return DB::transaction(function () use ($order, $nextStatus, $staff, $meta) {
            $locked = Order::query()->with('items.variant')->lockForUpdate()->findOrFail($order->id);
            $previousStatus = $locked->status;

            if ($previousStatus === $nextStatus) {
                return $locked;
            }

            // Single source of truth for which status changes are allowed
            $from = OrderStatus::tryFrom((string) $previousStatus);
            $to = OrderStatus::tryFrom($nextStatus);
            if (! $from || ! $to || ! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => ["Order cannot transition from {$previousStatus} to {$nextStatus}."],
                ]);
            }

            if ($nextStatus === Order::STATUS_SHIPPED) {
                if ($locked->payment_method === 'bank_transfer' && $locked->payment_status !== Order::PAYMENT_VERIFIED) {
                    throw ValidationException::withMessages([
                        'status' => ['Cannot ship a bank-transfer order before payment verification by staff.'],
                    ]);
                }

                if (! in_array($previousStatus, [Order::STATUS_PROCESSING, Order::STATUS_COD_CONFIRMED], true)) {
                    throw ValidationException::withMessages([
                        'status' => ["Order cannot transition from {$previousStatus} to shipped."],
                    ]);
                }

                foreach ($locked->items as $item) {
                    if ($item->variant) {
                        $this->inventory->commit($item->variant, $item->quantity, 'order', $locked->id, $staff);
                    }
                }

                $locked->update([
                    'status' => Order::STATUS_SHIPPED,
                    'courier_name' => $meta['courier_name'] ?? $locked->courier_name ?? 'Pakistan Post / TCS',
                    'tracking_number' => $meta['tracking_number'] ?? $locked->tracking_number,
                    'shipped_at' => now(),
                ]);
            } elseif ($nextStatus === Order::STATUS_DELIVERED) {
                if ($previousStatus !== Order::STATUS_SHIPPED) {
                    throw ValidationException::withMessages([
                        'status' => ['Order must be in shipped state before marking delivered.'],
                    ]);
                }

                $updates = [
                    'status' => Order::STATUS_DELIVERED,
                    'delivered_at' => now(),
                ];

                if ($locked->payment_method === 'cod' && $locked->payment_status === Order::PAYMENT_COD_PENDING) {
                    $updates['payment_status'] = Order::PAYMENT_VERIFIED;
                    $locked->payments()->where('method', 'cod')->update([
                        'status' => 'verified',
                        'verified_by' => $staff->id,
                        'verified_at' => now(),
                        'staff_notes' => 'COD cash collected upon delivery.',
                    ]);
                }

                $locked->update($updates);
            } elseif ($nextStatus === Order::STATUS_PROCESSING) {
                if ($locked->payment_method === 'bank_transfer' && $locked->payment_status !== Order::PAYMENT_VERIFIED) {
                    throw ValidationException::withMessages([
                        'status' => ['Bank transfer payment must be verified before moving order to processing.'],
                    ]);
                }
                $locked->update([
                    'status' => Order::STATUS_PROCESSING,
                    'confirmed_at' => $locked->confirmed_at ?? now(),
                ]);
            } elseif ($nextStatus === Order::STATUS_CANCELLED) {
                if (in_array($previousStatus, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true)) {
                    throw ValidationException::withMessages([
                        'status' => ["Cannot cancel an order in {$previousStatus} status."],
                    ]);
                }

                foreach ($locked->items as $item) {
                    if ($item->variant) {
                        $this->inventory->release(
                            $item->variant,
                            $item->quantity,
                            'order',
                            $locked->id,
                            $staff,
                            $meta['cancellation_reason'] ?? 'Cancelled by staff'
                        );
                    }
                }

                $locked->update([
                    'status' => Order::STATUS_CANCELLED,
                    'cancellation_reason' => $meta['cancellation_reason'] ?? 'Cancelled by staff',
                    'cancelled_at' => now(),
                ]);
            } else {
                $locked->update(['status' => $nextStatus]);
            }

            if (in_array($locked->status, [Order::STATUS_SHIPPED, Order::STATUS_CANCELLED], true)) {
                OrderNotifier::send($locked, $locked->status === Order::STATUS_SHIPPED
                    ? OrderUpdateNotification::EVENT_SHIPPED
                    : OrderUpdateNotification::EVENT_CANCELLED);
            }

            $this->audit->record(
                'commerce.order.status_updated',
                $locked,
                ['status' => $previousStatus],
                ['status' => $locked->status, 'tracking_number' => $locked->tracking_number],
                $staff
            );

            return $locked->fresh(['items', 'payments']);
        });
    }

    public function customerCancel(Order $order, string $reason, ?User $customer = null): Order
    {
        return DB::transaction(function () use ($order, $reason, $customer) {
            $locked = Order::query()->with('items.variant')->lockForUpdate()->findOrFail($order->id);

            if (! $locked->canCustomerCancel()) {
                throw ValidationException::withMessages([
                    'order' => ['This order has already entered processing/shipping and can no longer be self-cancelled.'],
                ]);
            }

            foreach ($locked->items as $item) {
                if ($item->variant) {
                    $this->inventory->release(
                        $item->variant,
                        $item->quantity,
                        'order',
                        $locked->id,
                        $customer,
                        "Customer cancellation: {$reason}"
                    );
                }
            }

            $locked->update([
                'status' => Order::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            OrderNotifier::send($locked, OrderUpdateNotification::EVENT_CANCELLED);

            $this->audit->record(
                'commerce.order.cancelled_by_customer',
                $locked,
                null,
                ['reason' => $reason],
                $customer
            );

            return $locked->fresh();
        });
    }

    public function requestReturn(Order $order, array $data, ?User $customer = null): OrderReturnRequest
    {
        if (! $order->canRequestReturn()) {
            throw ValidationException::withMessages([
                'order' => ['Returns can only be requested for delivered orders.'],
            ]);
        }

        if ($order->returnRequests()->whereIn('status', ['requested', 'approved'])->exists()) {
            throw ValidationException::withMessages([
                'order' => ['An active return request already exists for this order.'],
            ]);
        }

        $returnRequest = OrderReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $customer?->id ?? $order->user_id,
            'reason' => $data['reason'],
            'details' => $data['details'],
            'status' => 'requested',
        ]);

        $this->audit->record(
            'commerce.order.return_requested',
            $returnRequest,
            null,
            ['order_number' => $order->order_number, 'reason' => $data['reason']],
            $customer
        );

        return $returnRequest;
    }

    public function resolveReturnRequest(
        OrderReturnRequest $returnRequest,
        string $status,
        string $notes,
        User $staff,
        bool $restockItems = false
    ): OrderReturnRequest {
        return DB::transaction(function () use ($returnRequest, $status, $notes, $staff, $restockItems) {
            $locked = OrderReturnRequest::query()->with('order.items.variant')->lockForUpdate()->findOrFail($returnRequest->id);
            $previous = $locked->status;

            $locked->update([
                'status' => $status,
                'resolution_notes' => $notes,
                'reviewed_by' => $staff->id,
                'reviewed_at' => now(),
            ]);

            if ($status === 'received_and_restocked' && $previous !== 'received_and_restocked') {
                foreach ($locked->order->items as $item) {
                    if ($item->variant && $restockItems) {
                        $this->inventory->adjustStock(
                            $item->variant,
                            $item->quantity,
                            "Restocked from approved return on Order {$locked->order->order_number}",
                            $staff
                        );
                    }
                }
                $locked->order->update(['status' => Order::STATUS_RETURNED]);
            }

            $this->audit->record(
                'commerce.return.resolved',
                $locked,
                ['status' => $previous],
                ['status' => $status, 'restock_items' => $restockItems],
                $staff
            );

            return $locked->fresh();
        });
    }
}
