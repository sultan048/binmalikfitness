<?php

namespace App\Domain\Platform;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\Cart;
use App\Models\CoachingIntake;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DataRetentionAndSecurityService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Purges expired password reset tokens, expired idempotency keys, and stale guest carts.
     *
     * @return array{expired_password_tokens: int, expired_idempotency_keys: int, stale_guest_carts: int}
     */
    public function pruneExpiredSecurityTokens(?User $actor = null): array
    {
        $expiredPasswordTokens = 0;
        if (Schema::hasTable('password_reset_tokens')) {
            $expiredPasswordTokens = DB::table('password_reset_tokens')
                ->where('created_at', '<', now()->subMinutes(60))
                ->delete();
        }

        $expiredIdempotencyKeys = 0;
        if (Schema::hasTable('idempotency_keys')) {
            $column = Schema::hasColumn('idempotency_keys', 'expires_at') ? 'expires_at' : 'created_at';
            $cutoff = $column === 'expires_at' ? now() : now()->subDays(7);
            $expiredIdempotencyKeys = DB::table('idempotency_keys')
                ->where($column, '<', $cutoff)
                ->delete();
        }

        $staleGuestCarts = Cart::query()
            ->whereNull('user_id')
            ->where('updated_at', '<', now()->subDays(30))
            ->delete();

        $summary = [
            'expired_password_tokens' => $expiredPasswordTokens,
            'expired_idempotency_keys' => $expiredIdempotencyKeys,
            'stale_guest_carts' => $staleGuestCarts,
        ];

        $this->audit->record(
            action: 'security.retention.tokens_pruned',
            subject: null,
            oldValues: null,
            newValues: $summary,
            actor: $actor,
        );

        return $summary;
    }

    /**
     * Anonymises a customer's PII and confidential health records upon an approved account deletion request,
     * while strictly preserving immutable financial order & payment ledger totals.
     */
    public function anonymiseCustomerAccount(PrivacyRequest $privacyRequest, User $admin): User
    {
        if (! $admin->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            abort(403, 'Only Administrators may execute account PII anonymisation.');
        }

        $customer = User::withTrashed()->findOrFail($privacyRequest->user_id);

        if ($customer->isPrivilegedStaff()) {
            throw ValidationException::withMessages([
                'user' => ['Privileged staff accounts cannot be anonymised through the customer deletion workflow.'],
            ]);
        }

        return DB::transaction(function () use ($customer, $privacyRequest, $admin) {
            $anonLabel = "Anonymised Customer #{$customer->id}";
            $anonEmail = "deleted.user.{$customer->id}@anonymised.invalid";

            // 1. Scrub saved delivery addresses & biometric customer profile
            $customer->addresses()->delete();
            CustomerProfile::where('user_id', $customer->id)->update([
                'date_of_birth' => null,
                'height_cm' => null,
                'weight_kg' => null,
                'dietary_preferences' => null,
            ]);

            // 2. Scrub confidential medical/health intake notes
            CoachingIntake::where('user_id', $customer->id)->update([
                'health_notes_encrypted' => null,
                'allergies' => '[REDACTED PER APPROVED PRIVACY REQUEST]',
                'dietary_restrictions' => null,
                'review_flag_reason' => null,
                'reviewer_notes' => null,
            ]);

            // 3. Scrub customer PII on orders while strictly preserving all financial paisa amounts & payment statuses
            Order::where('user_id', $customer->id)->update([
                'customer_name' => $anonLabel,
                'customer_email' => $anonEmail,
                'customer_phone' => 'REDACTED',
                'shipping_address_snapshot' => json_encode(['redacted' => true]),
                'customer_notes' => null,
            ]);

            // 4. Anonymise and deactivate the User record (soft-deleted to preserve foreign key integrity)
            $customer->update([
                'name' => $anonLabel,
                'email' => $anonEmail,
                'phone' => null,
                'status' => 'deactivated',
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ]);

            if (! $customer->trashed()) {
                $customer->delete();
            }

            // 5. Invalidate any active sessions for the anonymised user
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $customer->id)->delete();
            }

            $privacyRequest->update([
                'status' => PrivacyRequest::STATUS_COMPLETED,
                'resolution_notes' => 'Account PII and confidential health records anonymised; financial ledger totals preserved.',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $this->audit->record(
                action: 'privacy.account.anonymised',
                subject: $privacyRequest,
                oldValues: ['user_id' => $customer->id],
                newValues: ['anonymised_label' => $anonLabel, 'status' => 'completed'],
                actor: $admin,
            );

            return $customer->fresh();
        });
    }
}
