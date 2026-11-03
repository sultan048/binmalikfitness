<?php

namespace App\Domain\Payments;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Media\PrivateFileService;
use App\Domain\Platform\AuditLogger;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PrivateFileService $privateFiles,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Stores a customer's manual bank transfer receipt on the private disk and records the reference.
     * NEVER marks the payment or order as verified/paid automatically.
     */
    public function submitBankTransferProof(
        Order $order,
        array $data,
        UploadedFile $receiptFile,
        ?User $actor = null
    ): Payment {
        if ($order->payment_method !== 'bank_transfer') {
            throw ValidationException::withMessages([
                'payment' => ['Bank transfer proof can only be uploaded for Manual Bank Transfer orders.'],
            ]);
        }

        if ($order->payment_status === Order::PAYMENT_VERIFIED) {
            throw ValidationException::withMessages([
                'payment' => ['This order payment has already been verified by staff.'],
            ]);
        }

        // Proof may only be attached while the order is still waiting for payment; otherwise a cancelled,
        // shipped or delivered order would be pushed back to awaiting_payment.
        if (! in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_AWAITING_PAYMENT], true)) {
            throw ValidationException::withMessages([
                'payment' => ['Payment proof can only be submitted for orders that are awaiting payment.'],
            ]);
        }

        // Guest orders have no account, so receipts are held under an active administrator.
        $owner = $order->user ?? $actor ?? $this->guestReceiptOwner();

        if (! $owner) {
            throw ValidationException::withMessages([
                'payment' => ['Unable to store the receipt right now. Please sign in or contact support.'],
            ]);
        }

        return DB::transaction(function () use ($order, $data, $receiptFile, $owner, $actor) {
            $privateFile = $this->privateFiles->store(
                uploader: $actor ?? $owner,
                owner: $owner,
                file: $receiptFile,
                category: 'payment_receipt',
                isConfidentialHealth: false,
                consentType: null,
            );

            $payment = $order->payments()->where('method', 'bank_transfer')->first();

            if (! $payment) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'method' => 'bank_transfer',
                    'status' => 'proof_submitted',
                    'amount_paisa' => $order->total_paisa,
                    'currency' => 'PKR',
                ]);
            }

            $payment->update([
                'status' => 'proof_submitted',
                'transaction_reference' => trim($data['transaction_reference']),
                'sender_bank_name' => $data['sender_bank_name'] ?? null,
                'sender_account_last4' => $data['sender_account_last4'] ?? null,
                'proof_private_file_id' => $privateFile->id,
            ]);

            // Crucial: Order remains awaiting_payment and payment_status is proof_submitted (NOT verified/paid)
            $order->update([
                'payment_status' => Order::PAYMENT_PROOF_SUBMITTED,
                'status' => Order::STATUS_AWAITING_PAYMENT,
            ]);

            $this->audit->record(
                'payment.proof.submitted',
                $payment,
                null,
                [
                    'order_number' => $order->order_number,
                    'transaction_reference' => $payment->transaction_reference,
                    'sender_bank_name' => $payment->sender_bank_name,
                    'order_payment_status' => $order->payment_status,
                ],
                $actor
            );

            return $payment->fresh();
        });
    }

    private function guestReceiptOwner(): ?User
    {
        return User::role(RolePermissionMatrix::ROLE_ADMIN)
            ->where('status', 'active')
            ->when(app()->isProduction(), fn ($q) => $q->where('is_demo', false))
            ->orderBy('is_demo')
            ->orderBy('id')
            ->first();
    }

    /**
     * Staff verification of payment. Idempotent and guarded against duplicate transaction references.
     */
    public function verifyPayment(Payment $payment, User $staff, ?string $notes = null): Payment
    {
        return DB::transaction(function () use ($payment, $staff, $notes) {
            $locked = Payment::query()->with('order')->lockForUpdate()->findOrFail($payment->id);

            // Idempotency guard: if already verified, return without side effects
            if ($locked->status === 'verified') {
                return $locked;
            }

            if ($locked->transaction_reference) {
                $duplicateVerified = Payment::query()
                    ->where('id', '!=', $locked->id)
                    ->where('status', 'verified')
                    ->where('transaction_reference', $locked->transaction_reference)
                    ->exists();

                if ($duplicateVerified) {
                    throw ValidationException::withMessages([
                        'transaction_reference' => ["Transaction reference {$locked->transaction_reference} has already been verified on another payment."],
                    ]);
                }
            }

            $locked->update([
                'status' => 'verified',
                'verified_by' => $staff->id,
                'verified_at' => now(),
                'staff_notes' => $notes,
            ]);

            $order = $locked->order;
            $nextOrderStatus = in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_AWAITING_PAYMENT, Order::STATUS_COD_CONFIRMED], true)
                ? Order::STATUS_PROCESSING
                : $order->status;

            $order->update([
                'payment_status' => Order::PAYMENT_VERIFIED,
                'status' => $nextOrderStatus,
                'confirmed_at' => $order->confirmed_at ?? now(),
            ]);

            // Trigger Stage 4 digital entitlement & coaching subscription activation when payment is verified
            app(\App\Domain\Coaching\CoachingService::class)->fulfilVerifiedOrder($order, $staff);

            // Trigger Stage 5 physical gym membership activation/renewal when payment is verified
            app(\App\Domain\Gym\GymManagementService::class)->fulfilVerifiedOrder($order, $staff);

            $this->audit->record(
                'payment.verified',
                $locked,
                ['status' => 'proof_submitted'],
                [
                    'status' => 'verified',
                    'order_number' => $order->order_number,
                    'amount_paisa' => $locked->amount_paisa,
                    'transaction_reference' => $locked->transaction_reference,
                ],
                $staff
            );

            return $locked->fresh('order');
        });
    }

    public function rejectPayment(Payment $payment, User $staff, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $staff, $reason) {
            $locked = Payment::query()->with('order')->lockForUpdate()->findOrFail($payment->id);

            if ($locked->status === 'verified') {
                throw ValidationException::withMessages([
                    'payment' => ['Cannot reject an already verified payment. Use the refund workflow instead.'],
                ]);
            }

            $locked->update([
                'status' => 'rejected',
                'staff_notes' => $reason,
            ]);

            $locked->order->update([
                'payment_status' => Order::PAYMENT_UNPAID,
                'status' => Order::STATUS_AWAITING_PAYMENT,
            ]);

            $this->audit->record(
                'payment.rejected',
                $locked,
                null,
                ['order_number' => $locked->order->order_number, 'reason' => $reason],
                $staff
            );

            return $locked->fresh('order');
        });
    }

    /**
     * Admin-only refund workflow with immutable refund records and audit logging.
     */
    public function issueRefund(
        Order $order,
        int $amountPaisa,
        string $reason,
        ?string $reference,
        User $admin
    ): Refund {
        if (! $admin->can('refunds.approve')) {
            abort(403, 'Only authorized Administrators may issue refunds.');
        }

        if ($amountPaisa <= 0) {
            throw ValidationException::withMessages(['amount' => ['Refund amount must be greater than zero.']]);
        }

        return DB::transaction(function () use ($order, $amountPaisa, $reason, $reference, $admin) {
            $lockedOrder = Order::query()->with('payments')->lockForUpdate()->findOrFail($order->id);

            if (! in_array($lockedOrder->payment_status, [Order::PAYMENT_VERIFIED, Order::PAYMENT_PARTIALLY_REFUNDED], true)) {
                throw ValidationException::withMessages([
                    'refund' => ['Refunds can only be issued for orders with verified payments.'],
                ]);
            }

            $maxRefundable = $lockedOrder->total_paisa - $lockedOrder->refunded_paisa;
            if ($amountPaisa > $maxRefundable) {
                throw ValidationException::withMessages([
                    'amount' => ['Refund amount exceeds remaining refundable balance for this order.'],
                ]);
            }

            $verifiedPayment = $lockedOrder->payments->firstWhere('status', 'verified');

            $refund = Refund::create([
                'order_id' => $lockedOrder->id,
                'payment_id' => $verifiedPayment?->id,
                'amount_paisa' => $amountPaisa,
                'reason' => $reason,
                'reference' => $reference ?: 'REF-'.now()->format('YmdHis'),
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $newRefundedTotal = $lockedOrder->refunded_paisa + $amountPaisa;
            $isFullyRefunded = $newRefundedTotal >= $lockedOrder->total_paisa;

            $lockedOrder->update([
                'refunded_paisa' => $newRefundedTotal,
                'payment_status' => $isFullyRefunded ? Order::PAYMENT_REFUNDED : Order::PAYMENT_PARTIALLY_REFUNDED,
                'status' => $isFullyRefunded ? Order::STATUS_REFUNDED : $lockedOrder->status,
            ]);

            if ($isFullyRefunded && $verifiedPayment) {
                $verifiedPayment->update(['status' => 'refunded']);
            }

            $this->audit->record(
                'payment.refund.issued',
                $refund,
                ['refunded_paisa' => $lockedOrder->refunded_paisa - $amountPaisa],
                [
                    'order_number' => $lockedOrder->order_number,
                    'refund_amount_paisa' => $amountPaisa,
                    'total_refunded_paisa' => $newRefundedTotal,
                    'reason' => $reason,
                ],
                $admin
            );

            return $refund;
        });
    }
}
