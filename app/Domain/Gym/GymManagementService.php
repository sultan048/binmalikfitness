<?php

namespace App\Domain\Gym;

use App\Domain\Platform\AuditLogger;
use App\Domain\Platform\EntitlementService;
use App\Models\Enquiry;
use App\Models\GymCheckIn;
use App\Models\GymClass;
use App\Models\GymClassBooking;
use App\Models\GymHoliday;
use App\Models\GymMembership;
use App\Models\MembershipPlan;
use App\Models\OpeningHour;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GymManagementService
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create an Order for purchasing or renewing a physical Gym Membership Plan.
     * Supports 'bank_transfer', 'cod', or 'counter_cash'. Never auto-verifies payment.
     */
    public function createMembershipOrder(
        User $user,
        MembershipPlan $plan,
        string $paymentMethod = 'bank_transfer',
        bool $isRenewal = false
    ): Order {
        if ($plan->status !== 'active') {
            throw new InvalidArgumentException('This membership package is not currently available.');
        }

        return DB::transaction(function () use ($user, $plan, $paymentMethod, $isRenewal): Order {
            $registrationFee = $isRenewal ? 0 : (int) $plan->registration_fee;
            $subtotal = (int) $plan->price + $registrationFee;
            $orderNumber = Order::generateNumber('BMF-GYM');

            $order = Order::create([
                'order_number' => $orderNumber,
                'access_token' => Str::random(48),
                'idempotency_key' => (string) Str::uuid(),
                'user_id' => $user->id,
                'customer_email' => $user->email,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone ?: '0300-0000000',
                'shipping_address_snapshot' => [
                    'recipient_name' => $user->name,
                    'phone' => $user->phone ?: '0300-0000000',
                    'line1' => 'Physical Gym Membership Desk',
                    'city' => 'Configurable Gym City',
                    'province' => 'Punjab',
                ],
                'shipping_method' => 'in_gym_counter',
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'cod' ? Order::PAYMENT_COD_PENDING : Order::PAYMENT_UNPAID,
                'status' => $paymentMethod === 'cod' ? Order::STATUS_COD_CONFIRMED : Order::STATUS_AWAITING_PAYMENT,
                'business_line' => 'gym',
                'currency' => 'PKR',
                'subtotal_paisa' => $subtotal,
                'discount_paisa' => 0,
                'shipping_paisa' => 0,
                'tax_paisa' => 0,
                'total_paisa' => $subtotal,
                'customer_notes' => ($isRenewal ? 'Membership Renewal: ' : 'New Gym Membership: ').$plan->name,
                'placed_at' => now(),
                'is_demo' => (bool) $user->is_demo,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'business_line' => 'gym',
                'purchasable_type' => MembershipPlan::class,
                'purchasable_id' => $plan->id,
                'product_name_snapshot' => $plan->name.($isRenewal ? ' (Renewal)' : ' (Incl. Registration)'),
                'variant_label_snapshot' => $plan->duration_days.' Days · '.$plan->access_hours,
                'sku_snapshot' => 'GYM-'.strtoupper(Str::slug($plan->slug)),
                'quantity' => 1,
                'unit_price_paisa' => $subtotal,
                'line_total_paisa' => $subtotal,
                'weight_grams_snapshot' => 0,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'status' => 'pending',
                'amount_paisa' => $subtotal,
                'currency' => 'PKR',
                'transaction_reference' => 'PAY-'.$orderNumber,
            ]);

            // Create a pending_payment membership placeholder if user has no active membership yet
            $existingActive = GymMembership::query()
                ->where('user_id', $user->id)
                ->where('status', GymMembership::STATUS_ACTIVE)
                ->first();

            if (! $existingActive) {
                GymMembership::create([
                    'membership_number' => 'GYM-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                    'member_card_code' => 'BMF-CARD-'.strtoupper(Str::random(6)),
                    'user_id' => $user->id,
                    'membership_plan_id' => $plan->id,
                    'order_id' => $order->id,
                    'status' => GymMembership::STATUS_PENDING_PAYMENT,
                    'is_demo' => (bool) $user->is_demo,
                ]);
            }

            $this->audit->record('gym.membership.order_created', $order, null, [
                'plan_slug' => $plan->slug,
                'is_renewal' => $isRenewal,
                'payment_method' => $paymentMethod,
                'total_paisa' => $subtotal,
            ], $user);

            return $order->load(['items', 'payments']);
        });
    }

    /**
     * Fulfil gym membership order items when payment is verified by staff.
     */
    public function fulfilVerifiedOrder(Order $order, ?User $verifiedBy = null): void
    {
        $order->loadMissing(['items', 'user']);
        if (! $order->user) {
            return;
        }

        $hasGymItem = false;

        foreach ($order->items as $item) {
            if ($item->purchasable_type === MembershipPlan::class && $item->purchasable_id) {
                $plan = MembershipPlan::find($item->purchasable_id);
                if ($plan) {
                    $hasGymItem = true;
                    $this->activateOrRenewMembership($order->user, $plan, $order, $verifiedBy);
                }
            }
        }

        if ($hasGymItem && $order->business_line === 'gym' && in_array($order->status, ['awaiting_payment', 'cod_confirmed', 'processing'], true)) {
            $order->update([
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);
        }
    }

    /**
     * Activate a pending membership or extend an existing membership from max(now(), ends_at).
     */
    public function activateOrRenewMembership(
        User $user,
        MembershipPlan $plan,
        ?Order $order = null,
        ?User $actor = null,
        ?User $assignedTrainer = null
    ): GymMembership {
        return DB::transaction(function () use ($user, $plan, $order, $actor, $assignedTrainer): GymMembership {
            $existingActive = GymMembership::query()
                ->where('user_id', $user->id)
                ->where('status', GymMembership::STATUS_ACTIVE)
                ->latest('id')
                ->first();

            if ($existingActive) {
                $baseDate = ($existingActive->ends_at && $existingActive->ends_at->isFuture())
                    ? $existingActive->ends_at
                    : now();
                $newEndsAt = $baseDate->copy()->addDays($plan->duration_days);

                $existingActive->update([
                    'membership_plan_id' => $plan->id,
                    'order_id' => $order?->id ?? $existingActive->order_id,
                    'assigned_trainer_id' => $assignedTrainer?->id ?? $existingActive->assigned_trainer_id,
                    'ends_at' => $newEndsAt,
                ]);

                $membership = $existingActive->fresh();
            } else {
                $pending = GymMembership::query()
                    ->where('user_id', $user->id)
                    ->where('status', GymMembership::STATUS_PENDING_PAYMENT)
                    ->when($order, fn ($q) => $q->where('order_id', $order->id))
                    ->latest('id')
                    ->first();

                $startsAt = now();
                $endsAt = now()->addDays($plan->duration_days);

                if ($pending) {
                    $pending->update([
                        'membership_plan_id' => $plan->id,
                        'order_id' => $order?->id ?? $pending->order_id,
                        'assigned_trainer_id' => $assignedTrainer?->id ?? $pending->assigned_trainer_id,
                        'status' => GymMembership::STATUS_ACTIVE,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                    ]);
                    $membership = $pending->fresh();
                } else {
                    $membership = GymMembership::create([
                        'membership_number' => 'GYM-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                        'member_card_code' => 'BMF-CARD-'.strtoupper(Str::random(6)),
                        'user_id' => $user->id,
                        'membership_plan_id' => $plan->id,
                        'order_id' => $order?->id,
                        'assigned_trainer_id' => $assignedTrainer?->id,
                        'status' => GymMembership::STATUS_ACTIVE,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'is_demo' => (bool) $user->is_demo,
                    ]);
                }
            }

            $this->entitlements->grant(
                user: $user,
                entitleable: $plan,
                source: $membership,
                startsAt: $membership->starts_at,
                endsAt: $membership->ends_at,
                actor: $actor ?? $user
            );

            $this->audit->record('gym.membership.activated_or_renewed', $membership, null, [
                'membership_number' => $membership->membership_number,
                'member_card_code' => $membership->member_card_code,
                'plan_slug' => $plan->slug,
                'ends_at' => $membership->ends_at?->toIso8601String(),
            ], $actor ?? $user);

            return $membership;
        });
    }

    /**
     * Staff counter registration / renewal with optional immediate offline payment verification.
     */
    public function registerOrRenewAtCounter(
        User $staff,
        User $member,
        MembershipPlan $plan,
        ?User $assignedTrainer = null,
        string $counterReceiptRef = '',
        bool $verifyPaymentImmediately = true
    ): GymMembership {
        if (! $staff->can('gym.memberships.manage')) {
            throw new AuthorizationException('You are not authorized to manage gym memberships.');
        }

        return DB::transaction(function () use ($staff, $member, $plan, $assignedTrainer, $counterReceiptRef, $verifyPaymentImmediately): GymMembership {
            $hasActive = GymMembership::query()
                ->where('user_id', $member->id)
                ->where('status', GymMembership::STATUS_ACTIVE)
                ->exists();

            $order = $this->createMembershipOrder($member, $plan, 'counter_cash', $hasActive);

            if ($verifyPaymentImmediately) {
                if (! $staff->can('payments.verify')) {
                    throw new AuthorizationException('You do not have permission to verify counter payments.');
                }

                $payment = $order->payments()->latest('id')->firstOrFail();
                $payment->update([
                    'status' => 'verified',
                    'transaction_reference' => $counterReceiptRef !== '' ? $counterReceiptRef : $payment->transaction_reference,
                    'verified_by' => $staff->id,
                    'verified_at' => now(),
                    'staff_notes' => 'Verified offline at physical gym counter by '.$staff->name,
                ]);

                $order->update([
                    'payment_status' => Order::PAYMENT_VERIFIED,
                    'status' => Order::STATUS_DELIVERED,
                    'confirmed_at' => now(),
                    'delivered_at' => now(),
                ]);

                $membership = $this->activateOrRenewMembership($member, $plan, $order, $staff, $assignedTrainer);

                $this->audit->record('gym.membership.counter_verified', $membership, null, [
                    'order_number' => $order->order_number,
                    'counter_receipt_ref' => $payment->transaction_reference,
                ], $staff);

                return $membership;
            }

            $pending = GymMembership::where('user_id', $member->id)->latest('id')->firstOrFail();
            if ($assignedTrainer) {
                $pending->update(['assigned_trainer_id' => $assignedTrainer->id]);
            }

            return $pending->fresh();
        });
    }

    /**
     * Generate a short-lived HMAC-SHA256 signed QR check-in token with unique nonce.
     */
    public function generateQrCheckInToken(GymMembership $membership, int $ttlSeconds = 600, ?string $customNonce = null): string
    {
        $nonce = $customNonce ?: Str::random(24);
        $expiresAt = now()->addSeconds($ttlSeconds)->timestamp;
        $payload = implode('|', [
            $membership->id,
            $membership->member_card_code,
            $nonce,
            $expiresAt,
        ]);

        $signature = hash_hmac('sha256', $payload, (string) config('app.key'));

        return base64_encode($payload.'|'.$signature);
    }

    /**
     * Staff-controlled QR check-in verification with cryptographic signature check,
     * expiry window check, replay protection (unique qr_nonce), and membership eligibility check.
     */
    public function verifyQrCheckInByStaff(User $staff, string $qrToken, ?string $entryNote = null): GymCheckIn
    {
        if (! $staff->can('gym.checkins.record')) {
            throw new AuthorizationException('Only authorized gym staff can verify QR check-ins.');
        }

        $decoded = base64_decode($qrToken, true);
        if (! is_string($decoded) || substr_count($decoded, '|') !== 4) {
            throw new InvalidArgumentException('Malformed QR check-in token.');
        }

        [$membershipId, $cardCode, $nonce, $expiresAt, $signature] = explode('|', $decoded, 5);
        $payload = implode('|', [$membershipId, $cardCode, $nonce, $expiresAt]);
        $expectedSignature = hash_hmac('sha256', $payload, (string) config('app.key'));

        if (! hash_equals($expectedSignature, $signature)) {
            $this->audit->record('gym.checkin.invalid_signature', null, null, ['card_code' => $cardCode], $staff);
            throw new InvalidArgumentException('Invalid QR check-in signature.');
        }

        if ((int) $expiresAt < now()->timestamp) {
            throw new InvalidArgumentException('This QR check-in pass has expired. Please refresh your member card.');
        }

        if (GymCheckIn::query()->where('qr_nonce', $nonce)->exists()) {
            $this->audit->record('gym.checkin.replay_blocked', null, null, [
                'membership_id' => (int) $membershipId,
                'qr_nonce' => $nonce,
            ], $staff);
            throw new InvalidArgumentException('This QR check-in token has already been used (replay attempt blocked).');
        }

        $membership = GymMembership::with(['user', 'plan'])->findOrFail((int) $membershipId);
        if ($membership->member_card_code !== $cardCode) {
            throw new InvalidArgumentException('Member card code mismatch.');
        }

        return $this->performCheckIn($staff, $membership, 'qr_staff_verified', $nonce, $entryNote);
    }

    /**
     * Manual staff desk check-in by GymMembership.
     */
    public function recordDeskCheckIn(User $staff, GymMembership $membership, ?string $entryNote = null): GymCheckIn
    {
        if (! $staff->can('gym.checkins.record')) {
            throw new AuthorizationException('Only authorized gym staff can record check-ins.');
        }

        return $this->performCheckIn($staff, $membership, 'desk_manual', null, $entryNote);
    }

    private function performCheckIn(
        User $staff,
        GymMembership $membership,
        string $method,
        ?string $qrNonce = null,
        ?string $entryNote = null
    ): GymCheckIn {
        if (! $membership->isCurrentlyEligible()) {
            $this->audit->record('gym.checkin.denied_ineligible', $membership, null, [
                'status' => $membership->status,
                'ends_at' => $membership->ends_at?->toIso8601String(),
            ], $staff);
            throw new InvalidArgumentException('Check-in denied: Membership ('.$membership->membership_number.') is '.$membership->status.' or expired.');
        }

        $todayHoliday = GymHoliday::query()
            ->whereDate('holiday_date', now()->toDateString())
            ->where('is_closed', true)
            ->first();

        if ($todayHoliday) {
            throw new InvalidArgumentException('Check-in denied: Physical gym is closed today for '.$todayHoliday->title.'.');
        }

        return DB::transaction(function () use ($staff, $membership, $method, $qrNonce, $entryNote): GymCheckIn {
            $checkIn = GymCheckIn::create([
                'gym_membership_id' => $membership->id,
                'user_id' => $membership->user_id,
                'recorded_by' => $staff->id,
                'check_in_method' => $method,
                'qr_nonce' => $qrNonce,
                'checked_in_at' => now(),
                'verification_status' => 'admitted',
                'entry_note' => $entryNote ?: 'Verified at gym reception by '.$staff->name,
            ]);

            $membership->update([
                'last_check_in_at' => now(),
                'total_visits_count' => $membership->total_visits_count + 1,
            ]);

            $this->audit->record('gym.checkin.recorded', $checkIn, null, [
                'membership_number' => $membership->membership_number,
                'method' => $method,
                'qr_nonce' => $qrNonce,
            ], $staff);

            return $checkIn;
        });
    }

    /**
     * Update membership status, trainer assignment, or send expiry reminder.
     */
    public function updateMembershipByStaff(User $staff, GymMembership $membership, array $data): GymMembership
    {
        if (! $staff->can('gym.memberships.manage')) {
            throw new AuthorizationException('You are not authorized to manage gym memberships.');
        }

        $updates = [];
        if (array_key_exists('status', $data) && in_array($data['status'], [
            GymMembership::STATUS_ACTIVE,
            GymMembership::STATUS_PAUSED,
            GymMembership::STATUS_EXPIRED,
            GymMembership::STATUS_CANCELLED,
        ], true)) {
            $updates['status'] = $data['status'];
        }

        if (array_key_exists('assigned_trainer_id', $data)) {
            $updates['assigned_trainer_id'] = $data['assigned_trainer_id'] ?: null;
        }

        if (array_key_exists('staff_notes', $data)) {
            $updates['staff_notes'] = $data['staff_notes'];
        }

        if (! empty($data['send_expiry_reminder'])) {
            $updates['expiry_reminder_sent_at'] = now();
        }

        $membership->update($updates);

        if (in_array($membership->status, [GymMembership::STATUS_EXPIRED, GymMembership::STATUS_CANCELLED], true)) {
            $this->entitlements->revokeBySource($membership, $staff, 'Membership marked '.$membership->status);
        }

        $this->audit->record('gym.membership.updated', $membership, null, $updates, $staff);

        return $membership->fresh(['user', 'plan', 'assignedTrainer']);
    }

    /**
     * Schedule or update a physical gym class.
     * Coaches can only manage classes assigned to themselves.
     */
    public function saveGymClass(User $actor, array $data, ?GymClass $gymClass = null): GymClass
    {
        if (! $actor->can('gym.classes.manage')) {
            throw new AuthorizationException('You do not have permission to manage gym classes.');
        }

        $isAdminOrManager = $actor->hasAnyRole(['admin', 'manager']);
        if (! $isAdminOrManager) {
            if ($gymClass && $gymClass->instructor_id !== $actor->id) {
                throw new AuthorizationException('Coaches can only manage their own assigned classes.');
            }
            $data['instructor_id'] = $actor->id;
        }

        return DB::transaction(function () use ($actor, $data, $gymClass): GymClass {
            $payload = [
                'title' => $data['title'],
                'slug' => $gymClass?->slug ?? Str::slug($data['title']).'-'.strtolower(Str::random(4)),
                'category' => $data['category'],
                'instructor_id' => $data['instructor_id'] ?? null,
                'day_of_week' => (int) $data['day_of_week'],
                'starts_at_time' => $data['starts_at_time'],
                'ends_at_time' => $data['ends_at_time'],
                'session_date' => $data['session_date'] ?? null,
                'room_or_zone' => $data['room_or_zone'] ?? 'Main Strength Floor',
                'capacity' => (int) $data['capacity'],
                'requires_active_membership' => (bool) ($data['requires_active_membership'] ?? true),
                'status' => $data['status'] ?? 'scheduled',
                'summary' => $data['summary'] ?? null,
            ];

            if ($gymClass) {
                $gymClass->update($payload);
                $record = $gymClass->fresh();
            } else {
                $record = GymClass::create($payload);
            }

            $this->audit->record('gym.class.saved', $record, null, [
                'title' => $record->title,
                'capacity' => $record->capacity,
                'instructor_id' => $record->instructor_id,
            ], $actor);

            return $record;
        });
    }

    /**
     * Customer books an eligible gym class with row-level lock capacity protection.
     */
    public function bookClass(User $user, GymClass $gymClass): GymClassBooking
    {
        return DB::transaction(function () use ($user, $gymClass): GymClassBooking {
            $lockedClass = GymClass::query()->whereKey($gymClass->id)->lockForUpdate()->firstOrFail();

            if ($lockedClass->status !== 'scheduled') {
                throw new InvalidArgumentException('This class session is not currently open for booking.');
            }

            $activeMembership = GymMembership::query()
                ->where('user_id', $user->id)
                ->where('status', GymMembership::STATUS_ACTIVE)
                ->first();

            if ($lockedClass->requires_active_membership && (! $activeMembership || ! $activeMembership->isCurrentlyEligible())) {
                throw new InvalidArgumentException('An active physical gym membership is required to book this class.');
            }

            $existingBooking = GymClassBooking::query()
                ->where('gym_class_id', $lockedClass->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingBooking && $existingBooking->status === 'booked') {
                return $existingBooking;
            }

            if ($lockedClass->booked_count >= $lockedClass->capacity) {
                throw new InvalidArgumentException('This class session has reached its maximum capacity ('.$lockedClass->capacity.' spots).');
            }

            if ($existingBooking) {
                $existingBooking->update([
                    'gym_membership_id' => $activeMembership?->id,
                    'status' => 'booked',
                    'booked_at' => now(),
                    'cancelled_at' => null,
                ]);
                $booking = $existingBooking->fresh();
            } else {
                $booking = GymClassBooking::create([
                    'gym_class_id' => $lockedClass->id,
                    'user_id' => $user->id,
                    'gym_membership_id' => $activeMembership?->id,
                    'status' => 'booked',
                    'booked_at' => now(),
                ]);
            }

            $lockedClass->increment('booked_count');

            $this->audit->record('gym.class.booked', $booking, null, [
                'gym_class_id' => $lockedClass->id,
                'booked_count' => $lockedClass->booked_count + 1,
                'capacity' => $lockedClass->capacity,
            ], $user);

            return $booking;
        });
    }

    /**
     * Cancel a customer class booking and release the spot.
     */
    public function cancelClassBooking(User $actor, GymClassBooking $booking): GymClassBooking
    {
        if ($actor->id !== $booking->user_id && ! $actor->can('gym.classes.manage')) {
            throw new AuthorizationException('You are not authorized to cancel this class booking.');
        }

        return DB::transaction(function () use ($actor, $booking): GymClassBooking {
            $lockedClass = GymClass::query()->whereKey($booking->gym_class_id)->lockForUpdate()->firstOrFail();

            if ($booking->status === 'booked') {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                if ($lockedClass->booked_count > 0) {
                    $lockedClass->decrement('booked_count');
                }

                $this->audit->record('gym.class.booking_cancelled', $booking, null, [
                    'gym_class_id' => $lockedClass->id,
                ], $actor);
            }

            return $booking->fresh();
        });
    }

    /**
     * Admin-only: Create or update a MembershipPlan package and its pricing.
     */
    public function saveMembershipPlan(User $actor, array $data, ?MembershipPlan $plan = null): MembershipPlan
    {
        if (! $actor->can('gym.plans.manage') || ! $actor->can('pricing.manage')) {
            throw new AuthorizationException('Only Admin can configure membership packages and pricing.');
        }

        $inclusions = isset($data['inclusions_csv'])
            ? array_values(array_filter(array_map('trim', explode(',', (string) $data['inclusions_csv']))))
            : ($plan?->inclusions ?? ['Full strength floor access', 'Locker room access']);

        $payload = [
            'name' => $data['name'],
            'slug' => $plan?->slug ?? Str::slug($data['name']).'-'.strtolower(Str::random(3)),
            'duration_days' => (int) $data['duration_days'],
            'price' => (int) round(((float) $data['price_pkr']) * 100),
            'registration_fee' => (int) round(((float) ($data['registration_fee_pkr'] ?? 0)) * 100),
            'access_hours' => $data['access_hours'] ?? 'All Operating Hours',
            'inclusions' => $inclusions,
            'status' => $data['status'] ?? 'active',
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ];

        if ($plan) {
            $plan->update($payload);
            $record = $plan->fresh();
        } else {
            $record = MembershipPlan::create($payload);
        }

        $this->audit->record('gym.plan.saved', $record, null, [
            'name' => $record->name,
            'price_paisa' => $record->price,
            'duration_days' => $record->duration_days,
        ], $actor);

        return $record;
    }

    /**
     * Admin-only: Update opening hours or add a holiday schedule.
     */
    public function updateOpeningHour(User $actor, OpeningHour $hour, array $data): OpeningHour
    {
        if (! $actor->can('settings.manage')) {
            throw new AuthorizationException('Only Admin can configure gym opening hours.');
        }

        $hour->update([
            'opens_at' => $data['opens_at'] ?? $hour->opens_at,
            'closes_at' => $data['closes_at'] ?? $hour->closes_at,
            'ladies_only_window' => $data['ladies_only_window'] ?? $hour->ladies_only_window,
            'peak_window' => $data['peak_window'] ?? $hour->peak_window,
            'is_closed' => (bool) ($data['is_closed'] ?? false),
            'note' => $data['note'] ?? $hour->note,
        ]);

        $this->audit->record('gym.opening_hours.updated', $hour, null, [
            'day_name' => $hour->day_name,
            'is_closed' => $hour->is_closed,
        ], $actor);

        return $hour->fresh();
    }

    public function saveHoliday(User $actor, array $data): GymHoliday
    {
        if (! $actor->can('settings.manage') && ! $actor->can('gym.memberships.manage')) {
            throw new AuthorizationException('You are not authorized to configure gym holiday schedules.');
        }

        $holiday = GymHoliday::updateOrCreate(
            ['holiday_date' => $data['holiday_date']],
            [
                'title' => $data['title'],
                'is_closed' => (bool) ($data['is_closed'] ?? true),
                'special_opens_at' => $data['special_opens_at'] ?? null,
                'special_closes_at' => $data['special_closes_at'] ?? null,
                'note' => $data['note'] ?? null,
            ]
        );

        $this->audit->record('gym.holiday.saved', $holiday, null, [
            'holiday_date' => $holiday->holiday_date->toDateString(),
            'is_closed' => $holiday->is_closed,
        ], $actor);

        return $holiday;
    }

    /**
     * Public or staff submission of a gym enquiry or trial session booking.
     */
    public function submitEnquiryOrTrial(array $data, ?string $ipAddress = null): Enquiry
    {
        $enquiry = Enquiry::create([
            'type' => $data['type'] ?? 'gym_trial',
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'city' => $data['city'] ?? null,
            'preferred_trial_date' => $data['preferred_trial_date'] ?? null,
            'preferred_time_window' => $data['preferred_time_window'] ?? null,
            'interested_membership_plan_id' => $data['interested_membership_plan_id'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'new',
            'ip_address' => $ipAddress,
        ]);

        $this->audit->record('gym.enquiry.submitted', $enquiry, null, [
            'type' => $enquiry->type,
            'preferred_trial_date' => $enquiry->preferred_trial_date?->toDateString(),
        ]);

        return $enquiry;
    }

    /**
     * Staff update on an enquiry or trial booking.
     */
    public function updateEnquiryByStaff(User $staff, Enquiry $enquiry, array $data): Enquiry
    {
        if (! $staff->can('enquiries.manage')) {
            throw new AuthorizationException('You are not authorized to manage enquiries and trial bookings.');
        }

        $enquiry->update([
            'status' => $data['status'] ?? $enquiry->status,
            'assigned_to' => $data['assigned_to'] ?? $staff->id,
            'staff_notes' => $data['staff_notes'] ?? $enquiry->staff_notes,
        ]);

        $this->audit->record('gym.enquiry.updated', $enquiry, null, [
            'status' => $enquiry->status,
            'assigned_to' => $enquiry->assigned_to,
        ], $staff);

        return $enquiry->fresh(['assignee', 'interestedPlan']);
    }
}
