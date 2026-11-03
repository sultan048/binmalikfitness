<?php

namespace App\Domain\Coaching;

use App\Domain\Identity\CoachAssignmentService;
use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Media\PrivateFileService;
use App\Domain\Platform\AuditLogger;
use App\Domain\Platform\EntitlementService;
use App\Models\CoachingCheckIn;
use App\Models\CoachingConsultation;
use App\Models\CoachingIntake;
use App\Models\CoachingMessage;
use App\Models\CoachingPackage;
use App\Models\CoachingSubscription;
use App\Models\ConsentRecord;
use App\Models\DietPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PersonalisedPlanRequest;
use App\Models\User;
use App\Models\WorkoutAssignment;
use App\Models\WorkoutProgram;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CoachingService
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly PrivateFileService $privateFiles,
        private readonly CoachAssignmentService $coachAssignments,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create an Order + Payment for a CoachingPackage, DietPlan, or WorkoutProgram.
     * Note: Subscriptions and paid entitlements remain inactive until payment is verified by staff.
     */
    public function createProgramOrder(
        User $user,
        Model $purchasable,
        string $paymentMethod = 'bank_transfer',
        ?CoachingSubscription $renewalSubscription = null
    ): Order {
        if (! in_array($paymentMethod, ['bank_transfer', 'cod'], true)) {
            throw new InvalidArgumentException('Unsupported payment method.');
        }

        [$businessLine, $title, $sku, $pricePaisa] = match (true) {
            $purchasable instanceof CoachingPackage => [
                'coaching',
                $purchasable->name,
                'COACH-'.strtoupper(Str::slug($purchasable->slug)),
                (int) $purchasable->price,
            ],
            $purchasable instanceof DietPlan => [
                'diet',
                $purchasable->title,
                'DIET-'.strtoupper(Str::slug($purchasable->slug)),
                (int) $purchasable->price,
            ],
            $purchasable instanceof WorkoutProgram => [
                'workout',
                $purchasable->title,
                'WORKOUT-'.strtoupper(Str::slug($purchasable->slug)),
                (int) $purchasable->price,
            ],
            default => throw new InvalidArgumentException('Unsupported program type for checkout.'),
        };

        return DB::transaction(function () use ($user, $purchasable, $businessLine, $title, $sku, $pricePaisa, $paymentMethod, $renewalSubscription) {
            $orderNumber = 'BMF-'.strtoupper($businessLine).'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));

            $order = Order::create([
                'order_number' => $orderNumber,
                'business_line' => $businessLine,
                'access_token' => Str::random(48),
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone ?? '0300-0000000',
                'shipping_address_snapshot' => [
                    'recipient_name' => $user->name,
                    'phone' => $user->phone ?? '0300-0000000',
                    'line1' => 'Digital Coaching & Program Entitlement Delivery',
                    'city' => 'Online Portal',
                    'province' => 'Pakistan',
                ],
                'shipping_method' => 'digital_entitlement',
                'payment_method' => $paymentMethod,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'payment_status' => Order::PAYMENT_UNPAID,
                'currency' => 'PKR',
                'subtotal_paisa' => $pricePaisa,
                'discount_paisa' => 0,
                'shipping_paisa' => 0,
                'tax_paisa' => 0,
                'total_paisa' => $pricePaisa,
                'refunded_paisa' => 0,
                'placed_at' => now(),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'business_line' => $businessLine,
                'purchasable_type' => $purchasable::class,
                'purchasable_id' => $purchasable->getKey(),
                'product_name_snapshot' => $title,
                'sku_snapshot' => substr($sku, 0, 60),
                'variant_label_snapshot' => strtoupper($businessLine).' ENTITLEMENT',
                'quantity' => 1,
                'unit_price_paisa' => $pricePaisa,
                'line_total_paisa' => $pricePaisa,
                'weight_grams_snapshot' => 0,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'status' => 'pending',
                'amount_paisa' => $pricePaisa,
                'currency' => 'PKR',
            ]);

            if ($purchasable instanceof CoachingPackage) {
                if ($renewalSubscription && $renewalSubscription->user_id === $user->id) {
                    $renewalSubscription->update(['latest_order_id' => $order->id]);
                } else {
                    CoachingSubscription::create([
                        'subscription_number' => 'SUB-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                        'user_id' => $user->id,
                        'coaching_package_id' => $purchasable->id,
                        'latest_order_id' => $order->id,
                        'status' => CoachingSubscription::STATUS_PENDING_PAYMENT,
                    ]);
                }
            }

            if ($purchasable instanceof DietPlan && $purchasable->type === 'personalised') {
                PersonalisedPlanRequest::create([
                    'user_id' => $user->id,
                    'diet_plan_id' => $purchasable->id,
                    'order_id' => $order->id,
                    'status' => PersonalisedPlanRequest::STATUS_AWAITING_PAYMENT,
                ]);
            }

            $this->audit->record(
                action: "program.order.created.{$businessLine}",
                subject: $order,
                oldValues: null,
                newValues: [
                    'order_number' => $order->order_number,
                    'purchasable' => $title,
                    'total_paisa' => $pricePaisa,
                ],
                actor: $user,
            );

            return $order->load(['items', 'payments']);
        });
    }

    /**
     * Fulfil digital entitlements and coaching subscriptions when an order's payment is verified by staff.
     */
    public function fulfilVerifiedOrder(Order $order, User $actor): void
    {
        if (! $order->user_id) {
            return;
        }

        $customer = User::find($order->user_id);
        if (! $customer) {
            return;
        }

        $order->loadMissing('items');
        $hasDigitalFulfilment = false;

        foreach ($order->items as $item) {
            if (! $item->purchasable_type || ! $item->purchasable_id) {
                continue;
            }

            if ($item->purchasable_type === CoachingPackage::class) {
                $package = CoachingPackage::find($item->purchasable_id);
                if ($package) {
                    $this->activateOrRenewCoachingSubscription($customer, $package, $order, $actor);
                    $hasDigitalFulfilment = true;
                }
            } elseif ($item->purchasable_type === DietPlan::class) {
                $dietPlan = DietPlan::with('templateVersion')->find($item->purchasable_id);
                if ($dietPlan) {
                    $endsAt = now()->addDays(max(30, (int) $dietPlan->duration_days));
                    $this->entitlements->grant($customer, $dietPlan, $order, now(), $endsAt, $actor);

                    if ($dietPlan->templateVersion) {
                        $this->entitlements->grant($customer, $dietPlan->templateVersion, $order, now(), $endsAt, $actor);
                    }

                    if ($dietPlan->type === 'personalised') {
                        $planRequest = PersonalisedPlanRequest::query()
                            ->where('order_id', $order->id)
                            ->where('user_id', $customer->id)
                            ->first();

                        if ($planRequest) {
                            $intake = $planRequest->intake;
                            $nextStatus = ! $intake
                                ? PersonalisedPlanRequest::STATUS_AWAITING_INTAKE
                                : ($intake->review_status === CoachingIntake::STATUS_FLAGGED_MEDICAL
                                    ? PersonalisedPlanRequest::STATUS_MEDICAL_HOLD
                                    : PersonalisedPlanRequest::STATUS_IN_PREPARATION);

                            $planRequest->update(['status' => $nextStatus]);
                        }
                    }

                    $hasDigitalFulfilment = true;
                }
            } elseif ($item->purchasable_type === WorkoutProgram::class) {
                $program = WorkoutProgram::find($item->purchasable_id);
                if ($program) {
                    $endsAt = now()->addWeeks(max(8, (int) $program->duration_weeks));
                    $this->entitlements->grant($customer, $program, $order, now(), $endsAt, $actor);
                    $hasDigitalFulfilment = true;
                }
            }
        }

        if ($hasDigitalFulfilment && $order->shipping_method === 'digital_entitlement') {
            $order->update([
                'status' => Order::STATUS_DELIVERED,
                'confirmed_at' => $order->confirmed_at ?? now(),
                'delivered_at' => now(),
            ]);
        }
    }

    public function activateOrRenewCoachingSubscription(
        User $customer,
        CoachingPackage $package,
        Order $order,
        User $actor,
        ?User $assignedCoach = null
    ): CoachingSubscription {
        $subscription = CoachingSubscription::query()
            ->where('user_id', $customer->id)
            ->where('latest_order_id', $order->id)
            ->first();

        if (! $subscription) {
            $subscription = CoachingSubscription::query()
                ->where('user_id', $customer->id)
                ->where('coaching_package_id', $package->id)
                ->latest('id')
                ->first();
        }

        if (! $subscription) {
            $subscription = CoachingSubscription::create([
                'subscription_number' => 'SUB-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'user_id' => $customer->id,
                'coaching_package_id' => $package->id,
                'latest_order_id' => $order->id,
                'status' => CoachingSubscription::STATUS_PENDING_PAYMENT,
            ]);
        }

        // If renewing an active subscription with remaining days, extend from current ends_at
        $baseEnd = ($subscription->status === CoachingSubscription::STATUS_ACTIVE && $subscription->ends_at && $subscription->ends_at->isFuture())
            ? $subscription->ends_at
            : now();

        $startsAt = $subscription->starts_at ?? now();
        $endsAt = $baseEnd->copy()->addDays((int) $package->duration_days);

        $subscription->update([
            'status' => CoachingSubscription::STATUS_ACTIVE,
            'coach_id' => $assignedCoach?->id ?? $subscription->coach_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'paused_at' => null,
        ]);

        if ($assignedCoach) {
            $this->coachAssignments->assign($actor, $assignedCoach, $customer, 'coaching');
        }

        $this->entitlements->grant(
            user: $customer,
            entitleable: $package,
            source: $subscription,
            startsAt: $startsAt,
            endsAt: $endsAt,
            actor: $actor,
        );

        $this->audit->record(
            action: 'coaching.subscription.activated',
            subject: $subscription,
            oldValues: null,
            newValues: [
                'subscription_number' => $subscription->subscription_number,
                'package_slug' => $package->slug,
                'ends_at' => $endsAt->toIso8601String(),
            ],
            actor: $actor,
        );

        return $subscription;
    }

    public function pauseSubscription(User $actor, CoachingSubscription $subscription, int $pauseDays, string $reason): CoachingSubscription
    {
        $this->authorizeSubscriptionMutation($actor, $subscription);

        if ($subscription->status !== CoachingSubscription::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Only active subscriptions can be paused.');
        }

        if ($pauseDays < 1 || ($subscription->pause_days_used + $pauseDays) > $subscription->max_pause_days) {
            throw new InvalidArgumentException("Pause duration exceeds the remaining allowed pause allowance ({$subscription->max_pause_days} days max).");
        }

        $newEndsAt = ($subscription->ends_at ?? now())->copy()->addDays($pauseDays);

        $subscription->update([
            'status' => CoachingSubscription::STATUS_PAUSED,
            'paused_at' => now(),
            'pause_days_used' => $subscription->pause_days_used + $pauseDays,
            'pause_reason' => $reason,
            'ends_at' => $newEndsAt,
        ]);

        $this->audit->record(
            action: 'coaching.subscription.paused',
            subject: $subscription,
            oldValues: ['status' => CoachingSubscription::STATUS_ACTIVE],
            newValues: [
                'status' => CoachingSubscription::STATUS_PAUSED,
                'pause_days' => $pauseDays,
                'new_ends_at' => $newEndsAt->toIso8601String(),
            ],
            actor: $actor,
        );

        return $subscription;
    }

    public function resumeSubscription(User $actor, CoachingSubscription $subscription): CoachingSubscription
    {
        $this->authorizeSubscriptionMutation($actor, $subscription);

        if ($subscription->status !== CoachingSubscription::STATUS_PAUSED) {
            throw new InvalidArgumentException('Only paused subscriptions can be resumed.');
        }

        $subscription->update([
            'status' => CoachingSubscription::STATUS_ACTIVE,
            'paused_at' => null,
        ]);

        $this->audit->record(
            action: 'coaching.subscription.resumed',
            subject: $subscription,
            oldValues: ['status' => CoachingSubscription::STATUS_PAUSED],
            newValues: ['status' => CoachingSubscription::STATUS_ACTIVE],
            actor: $actor,
        );

        return $subscription;
    }

    public function cancelSubscription(User $actor, CoachingSubscription $subscription, string $reason): CoachingSubscription
    {
        $this->authorizeSubscriptionMutation($actor, $subscription);

        $oldStatus = $subscription->status;

        $subscription->update([
            'status' => CoachingSubscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->entitlements->revokeBySource($subscription, $actor, $reason);

        $this->audit->record(
            action: 'coaching.subscription.cancelled',
            subject: $subscription,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => CoachingSubscription::STATUS_CANCELLED, 'reason' => $reason],
            actor: $actor,
        );

        return $subscription;
    }

    /**
     * Submit a client check-in with weight, measurements, biofeedback, and optional private progress photo.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitCheckIn(
        User $client,
        array $data,
        ?UploadedFile $photoFile = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): CoachingCheckIn {
        return DB::transaction(function () use ($client, $data, $photoFile, $ipAddress, $userAgent) {
            $privateFileId = null;
            $consentRecordId = null;
            $publicDisplayConsented = false;

            if ($photoFile) {
                if (empty($data['photo_storage_consent'])) {
                    throw new InvalidArgumentException('Explicit consent is required to upload a progress photo.');
                }

                $privateFile = $this->privateFiles->store(
                    uploader: $client,
                    owner: $client,
                    file: $photoFile,
                    category: 'progress_photo',
                    isConfidentialHealth: true,
                    consentType: 'progress_photo_private_storage',
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                );
                $privateFileId = $privateFile->id;
                $consentRecordId = $privateFile->consent_record_id;

                if (! empty($data['public_display_consent'])) {
                    $publicConsent = ConsentRecord::create([
                        'user_id' => $client->id,
                        'type' => 'public_transformation_display',
                        'granted_at' => now(),
                        'evidence_note' => 'Explicit public display consent for progress photo',
                        'ip_address' => $ipAddress,
                        'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
                    ]);
                    $consentRecordId = $publicConsent->id;
                    $publicDisplayConsented = true;
                }
            }

            $checkIn = CoachingCheckIn::create([
                'user_id' => $client->id,
                'coaching_subscription_id' => $data['coaching_subscription_id'] ?? null,
                'check_in_date' => $data['check_in_date'] ?? now()->toDateString(),
                'weight_kg' => (float) $data['weight_kg'],
                'waist_cm' => isset($data['waist_cm']) ? (float) $data['waist_cm'] : null,
                'chest_cm' => isset($data['chest_cm']) ? (float) $data['chest_cm'] : null,
                'hips_cm' => isset($data['hips_cm']) ? (float) $data['hips_cm'] : null,
                'arms_cm' => isset($data['arms_cm']) ? (float) $data['arms_cm'] : null,
                'thighs_cm' => isset($data['thighs_cm']) ? (float) $data['thighs_cm'] : null,
                'body_fat_pct' => isset($data['body_fat_pct']) ? (float) $data['body_fat_pct'] : null,
                'sleep_hours' => isset($data['sleep_hours']) ? (float) $data['sleep_hours'] : null,
                'water_liters' => isset($data['water_liters']) ? (float) $data['water_liters'] : null,
                'step_count' => isset($data['step_count']) ? (int) $data['step_count'] : null,
                'diet_adherence_score' => (int) ($data['diet_adherence_score'] ?? 8),
                'energy_score' => (int) ($data['energy_score'] ?? 8),
                'workouts_completed_this_week' => (int) ($data['workouts_completed_this_week'] ?? 0),
                'client_notes' => $data['client_notes'] ?? null,
                'progress_photo_file_id' => $privateFileId,
                'photo_consent_record_id' => $consentRecordId,
                'is_public_display_consented' => $publicDisplayConsented,
                'status' => 'submitted',
            ]);

            $this->audit->record(
                action: 'coaching.checkin.submitted',
                subject: $checkIn,
                oldValues: null,
                newValues: [
                    'weight_kg' => $checkIn->weight_kg,
                    'waist_cm' => $checkIn->waist_cm,
                    'has_photo' => $privateFileId !== null,
                    'public_display_consented' => $publicDisplayConsented,
                ],
                actor: $client,
            );

            return $checkIn;
        });
    }

    public function reviewCheckIn(User $coach, CoachingCheckIn $checkIn, string $feedback): CoachingCheckIn
    {
        $this->authorizeCoachAction($coach, $checkIn->user_id);

        $checkIn->update([
            'coach_feedback' => $feedback,
            'reviewed_by' => $coach->id,
            'reviewed_at' => now(),
            'status' => 'reviewed',
        ]);

        $this->audit->record(
            action: 'coaching.checkin.reviewed',
            subject: $checkIn,
            oldValues: null,
            newValues: ['client_id' => $checkIn->user_id, 'reviewed_by' => $coach->id],
            actor: $coach,
        );

        return $checkIn;
    }

    /**
     * Assign a custom workout program and weekly schedule to a coaching client.
     *
     * @param  array<string, mixed>  $data
     */
    public function assignWorkout(User $coach, User $client, WorkoutProgram $program, array $data): WorkoutAssignment
    {
        $this->authorizeCoachAction($coach, $client->id);

        $assignment = WorkoutAssignment::create([
            'user_id' => $client->id,
            'coaching_subscription_id' => $data['coaching_subscription_id'] ?? null,
            'workout_program_id' => $program->id,
            'assigned_by' => $coach->id,
            'title_override' => $data['title_override'] ?? null,
            'scheduled_days' => $data['scheduled_days'] ?? $program->structure,
            'custom_guidance' => $data['custom_guidance'] ?? null,
            'target_sessions_per_week' => (int) ($data['target_sessions_per_week'] ?? $program->days_per_week),
            'status' => 'active',
            'starts_on' => $data['starts_on'] ?? now()->toDateString(),
            'ends_on' => $data['ends_on'] ?? now()->addWeeks($program->duration_weeks)->toDateString(),
        ]);

        $this->entitlements->grant(
            user: $client,
            entitleable: $program,
            source: $assignment,
            startsAt: now(),
            endsAt: now()->addWeeks(max(8, (int) $program->duration_weeks)),
            actor: $coach,
        );

        $this->audit->record(
            action: 'coaching.workout.assigned',
            subject: $assignment,
            oldValues: null,
            newValues: ['client_id' => $client->id, 'program_slug' => $program->slug],
            actor: $coach,
        );

        return $assignment;
    }

    /**
     * Log a completed workout session for a customer's assigned workout.
     *
     * @param  array<string, mixed>  $sessionData
     */
    public function logWorkoutCompletion(User $client, WorkoutAssignment $assignment, array $sessionData): WorkoutAssignment
    {
        if ($assignment->user_id !== $client->id) {
            throw new AuthorizationException('You can only log workouts on your own workout assignments.');
        }

        $newCount = $assignment->completed_sessions_count + 1;

        $assignment->update([
            'completed_sessions_count' => $newCount,
            'last_completed_at' => now(),
            'last_session_log' => [
                'day_label' => $sessionData['day_label'] ?? 'Scheduled Session',
                'duration_minutes' => (int) ($sessionData['duration_minutes'] ?? 60),
                'rpe' => (int) ($sessionData['rpe'] ?? 8),
                'notes' => $sessionData['notes'] ?? null,
                'logged_at' => now()->toIso8601String(),
            ],
        ]);

        $this->audit->record(
            action: 'coaching.workout.completed',
            subject: $assignment,
            oldValues: null,
            newValues: ['completed_sessions_count' => $newCount],
            actor: $client,
        );

        return $assignment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function bookConsultation(User $client, array $data): CoachingConsultation
    {
        $subscription = CoachingSubscription::query()
            ->where('user_id', $client->id)
            ->where('status', CoachingSubscription::STATUS_ACTIVE)
            ->latest('id')
            ->first();

        $consultation = CoachingConsultation::create([
            'user_id' => $client->id,
            'coaching_subscription_id' => $subscription?->id,
            'coach_id' => $subscription?->coach_id,
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => (int) ($data['duration_minutes'] ?? 30),
            'channel' => $data['channel'] ?? 'whatsapp_video',
            'topic' => $data['topic'],
            'client_notes' => $data['client_notes'] ?? null,
            'status' => CoachingConsultation::STATUS_REQUESTED,
        ]);

        $this->audit->record(
            action: 'coaching.consultation.requested',
            subject: $consultation,
            oldValues: null,
            newValues: ['topic' => $consultation->topic, 'scheduled_at' => $consultation->scheduled_at->toIso8601String()],
            actor: $client,
        );

        return $consultation;
    }

    public function updateConsultationStatus(
        User $staff,
        CoachingConsultation $consultation,
        string $status,
        ?string $meetingReference = null,
        ?string $coachSummary = null
    ): CoachingConsultation {
        $this->authorizeCoachAction($staff, $consultation->user_id);

        if (! in_array($status, [
            CoachingConsultation::STATUS_CONFIRMED,
            CoachingConsultation::STATUS_COMPLETED,
            CoachingConsultation::STATUS_CANCELLED,
        ], true)) {
            throw new InvalidArgumentException('Invalid consultation status.');
        }

        $consultation->update([
            'coach_id' => $consultation->coach_id ?? $staff->id,
            'status' => $status,
            'meeting_reference' => $meetingReference ?? $consultation->meeting_reference,
            'coach_summary' => $coachSummary ?? $consultation->coach_summary,
        ]);

        $this->audit->record(
            action: 'coaching.consultation.updated',
            subject: $consultation,
            oldValues: null,
            newValues: ['status' => $status, 'client_id' => $consultation->user_id],
            actor: $staff,
        );

        return $consultation;
    }

    public function sendMessage(
        User $sender,
        User $client,
        string $body,
        ?int $subscriptionId = null
    ): CoachingMessage {
        if ($sender->id !== $client->id) {
            $this->authorizeCoachAction($sender, $client->id);
        }

        $message = CoachingMessage::create([
            'client_id' => $client->id,
            'coaching_subscription_id' => $subscriptionId,
            'sender_id' => $sender->id,
            'body' => trim($body),
        ]);

        $this->audit->record(
            action: 'coaching.message.sent',
            subject: $message,
            oldValues: null,
            newValues: ['client_id' => $client->id, 'sender_id' => $sender->id],
            actor: $sender,
        );

        return $message;
    }

    private function authorizeCoachAction(User $actor, int $clientId): void
    {
        if ($actor->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            return;
        }

        if (! $actor->can('coaching.deliver') && ! $actor->can('coaching.subscriptions.manage')) {
            throw new AuthorizationException('You do not have coaching delivery permissions.');
        }

        if (! $actor->isAssignedToClient($clientId)) {
            throw new AuthorizationException('You are not assigned to this coaching client.');
        }
    }

    private function authorizeSubscriptionMutation(User $actor, CoachingSubscription $subscription): void
    {
        if ($actor->id === $subscription->user_id || $actor->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            return;
        }

        if ($actor->can('coaching.subscriptions.manage') && $actor->isAssignedToClient($subscription->user_id)) {
            return;
        }

        throw new AuthorizationException('You are not authorized to modify this coaching subscription.');
    }
}
