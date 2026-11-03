<?php

namespace App\Domain\Coaching;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Platform\AuditLogger;
use App\Domain\Platform\EntitlementService;
use App\Models\CoachingIntake;
use App\Models\CoachingSubscription;
use App\Models\ConsentRecord;
use App\Models\CustomerProfile;
use App\Models\DietPlanVersion;
use App\Models\PersonalisedPlanRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IntakeAndPersonalisationService
{
    private const CLINICAL_KEYWORDS = [
        'diabetes',
        'diabetic',
        'insulin',
        'pregnant',
        'pregnancy',
        'hypertension',
        'blood pressure',
        'kidney',
        'renal',
        'cardiac',
        'heart disease',
        'surgery',
        'post-op',
        'thyroid',
        'pcos',
        'eating disorder',
        'anorexia',
        'bulimia',
        'epilepsy',
        'herniated',
    ];

    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Submit a 12-point personalization intake with explicit health consent and automatic clinical flagging.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitIntake(User $user, array $data, ?string $ipAddress = null, ?string $userAgent = null): CoachingIntake
    {
        $healthNotes = trim((string) ($data['health_notes'] ?? ''));
        $healthConsent = (bool) ($data['health_consent'] ?? false);

        if ($healthNotes !== '' && ! $healthConsent) {
            throw new InvalidArgumentException('Explicit health data consent is required when submitting health considerations.');
        }

        return DB::transaction(function () use ($user, $data, $healthNotes, $healthConsent, $ipAddress, $userAgent) {
            $consentRecord = null;
            if ($healthConsent) {
                $consentRecord = ConsentRecord::create([
                    'user_id' => $user->id,
                    'type' => 'health_intake_processing',
                    'granted_at' => now(),
                    'evidence_note' => 'Explicit health intake processing consent',
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
                ]);
            }

            $matchedFlags = $this->detectClinicalKeywords(
                implode(' ', [
                    $healthNotes,
                    (string) ($data['allergies'] ?? ''),
                    (string) ($data['dietary_restrictions'] ?? ''),
                ])
            );

            $needsReview = count($matchedFlags) > 0;
            $flagReason = $needsReview
                ? 'Clinical disclosure detected ('.implode(', ', $matchedFlags).') — requires qualified review & physician clearance.'
                : null;

            $intake = CoachingIntake::create([
                'user_id' => $user->id,
                'coaching_subscription_id' => $data['coaching_subscription_id'] ?? null,
                'diet_plan_id' => $data['diet_plan_id'] ?? null,
                'fitness_goal' => $data['fitness_goal'],
                'age' => (int) $data['age'],
                'height_cm' => (int) $data['height_cm'],
                'weight_kg' => (float) $data['weight_kg'],
                'activity_level' => $data['activity_level'],
                'training_experience' => $data['training_experience'],
                'food_preferences' => $data['food_preferences'],
                'allergies' => $data['allergies'] ?? null,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
                'equipment_availability' => $data['equipment_availability'],
                'preferred_schedule' => $data['preferred_schedule'],
                'health_consent_granted' => $healthConsent,
                'consent_record_id' => $consentRecord?->id,
                'health_notes_encrypted' => $healthNotes !== '' ? $healthNotes : null,
                'needs_professional_review' => $needsReview,
                'review_flag_reason' => $flagReason,
                'review_status' => $needsReview
                    ? CoachingIntake::STATUS_FLAGGED_MEDICAL
                    : CoachingIntake::STATUS_PENDING_REVIEW,
            ]);

            CustomerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'fitness_goal' => $data['fitness_goal'],
                    'height_cm' => (int) $data['height_cm'],
                    'weight_kg' => (float) $data['weight_kg'],
                    'activity_level' => $data['activity_level'],
                    'experience_level' => $data['training_experience'],
                    'equipment_access' => $data['equipment_availability'],
                ]
            );

            if (! empty($data['coaching_subscription_id'])) {
                CoachingSubscription::where('id', $data['coaching_subscription_id'])
                    ->where('user_id', $user->id)
                    ->update(['onboarding_completed_at' => now()]);
            }

            // Link to any open personalised plan request for this user
            $planRequestQuery = PersonalisedPlanRequest::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    PersonalisedPlanRequest::STATUS_AWAITING_INTAKE,
                    PersonalisedPlanRequest::STATUS_AWAITING_PAYMENT,
                    PersonalisedPlanRequest::STATUS_IN_PREPARATION,
                ]);

            if (! empty($data['diet_plan_id'])) {
                $planRequestQuery->where('diet_plan_id', $data['diet_plan_id']);
            }

            $planRequest = $planRequestQuery->latest('id')->first();
            if ($planRequest) {
                $nextStatus = $planRequest->status === PersonalisedPlanRequest::STATUS_AWAITING_PAYMENT
                    ? PersonalisedPlanRequest::STATUS_AWAITING_PAYMENT
                    : ($needsReview ? PersonalisedPlanRequest::STATUS_MEDICAL_HOLD : PersonalisedPlanRequest::STATUS_IN_PREPARATION);

                $planRequest->update([
                    'coaching_intake_id' => $intake->id,
                    'status' => $nextStatus,
                ]);
            }

            $this->audit->record(
                action: $needsReview ? 'coaching.intake.flagged_medical' : 'coaching.intake.submitted',
                subject: $intake,
                oldValues: null,
                newValues: [
                    'fitness_goal' => $intake->fitness_goal,
                    'needs_professional_review' => $needsReview,
                    'review_status' => $intake->review_status,
                ],
                actor: $user,
            );

            return $intake;
        });
    }

    public function reviewIntake(User $actor, CoachingIntake $intake, string $status, ?string $notes = null): CoachingIntake
    {
        $this->authorizeClientAccess($actor, $intake->user_id, 'health.view');

        if (! in_array($status, [CoachingIntake::STATUS_CLEARED, CoachingIntake::STATUS_FLAGGED_MEDICAL], true)) {
            throw new InvalidArgumentException('Invalid intake review status.');
        }

        $oldStatus = $intake->review_status;

        $intake->update([
            'review_status' => $status,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'reviewer_notes' => $notes,
        ]);

        if ($status === CoachingIntake::STATUS_CLEARED) {
            PersonalisedPlanRequest::query()
                ->where('coaching_intake_id', $intake->id)
                ->where('status', PersonalisedPlanRequest::STATUS_MEDICAL_HOLD)
                ->update(['status' => PersonalisedPlanRequest::STATUS_IN_PREPARATION]);
        }

        $this->audit->record(
            action: 'coaching.intake.reviewed',
            subject: $intake,
            oldValues: ['review_status' => $oldStatus],
            newValues: ['review_status' => $status, 'reviewer_notes' => $notes],
            actor: $actor,
        );

        return $intake;
    }

    /**
     * Prepare and publish a personalized DietPlanVersion or revision for a customer request.
     *
     * @param  array<string, mixed>  $data
     */
    public function deliverPersonalisedPlan(User $actor, PersonalisedPlanRequest $planRequest, array $data): DietPlanVersion
    {
        $this->authorizeClientAccess($actor, $planRequest->user_id, 'diet.requests.handle');

        $intake = $planRequest->intake;
        if ($intake && $intake->review_status === CoachingIntake::STATUS_FLAGGED_MEDICAL) {
            throw new InvalidArgumentException(
                'This intake is flagged for medical/professional review and cannot receive a prescribed diet plan until cleared.'
            );
        }

        return DB::transaction(function () use ($actor, $planRequest, $intake, $data) {
            $nextVersionNumber = (int) DietPlanVersion::query()
                ->where('diet_plan_id', $planRequest->diet_plan_id)
                ->where('user_id', $planRequest->user_id)
                ->max('version_number') + 1;

            $version = DietPlanVersion::create([
                'diet_plan_id' => $planRequest->diet_plan_id,
                'user_id' => $planRequest->user_id,
                'version_number' => $nextVersionNumber,
                'title' => $data['title'] ?? ($planRequest->dietPlan->title.' — Revision v'.$nextVersionNumber),
                'daily_calories' => (int) ($data['daily_calories'] ?? 2000),
                'protein_grams' => (int) ($data['protein_grams'] ?? 160),
                'carbs_grams' => (int) ($data['carbs_grams'] ?? 180),
                'fat_grams' => (int) ($data['fat_grams'] ?? 60),
                'hydration_target_liters' => (float) ($data['hydration_target_liters'] ?? 3.5),
                'meal_schedule' => $data['meal_schedule'],
                'substitutions' => $data['substitutions'] ?? [],
                'shopping_list' => $data['shopping_list'] ?? [],
                'coach_notes' => $data['coach_notes'] ?? null,
                'revision_note' => $data['revision_note'] ?? ('Prepared v'.$nextVersionNumber.' for '.$planRequest->user->name),
                'prepared_by' => $actor->id,
                'published_at' => now(),
                'is_active' => true,
            ]);

            $planRequest->update([
                'delivered_version_id' => $version->id,
                'status' => PersonalisedPlanRequest::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            if ($intake) {
                $intake->update(['review_status' => CoachingIntake::STATUS_PLAN_DELIVERED]);
            }

            $this->entitlements->grant(
                user: $planRequest->user,
                entitleable: $planRequest->dietPlan,
                source: $planRequest,
                startsAt: now(),
                endsAt: now()->addDays(max(30, (int) $planRequest->dietPlan->duration_days)),
                actor: $actor,
            );

            $this->entitlements->grant(
                user: $planRequest->user,
                entitleable: $version,
                source: $planRequest,
                startsAt: now(),
                endsAt: now()->addDays(max(30, (int) $planRequest->dietPlan->duration_days)),
                actor: $actor,
            );

            $this->audit->record(
                action: 'diet.personalised_version.delivered',
                subject: $version,
                oldValues: null,
                newValues: [
                    'request_id' => $planRequest->id,
                    'user_id' => $planRequest->user_id,
                    'version_number' => $version->version_number,
                ],
                actor: $actor,
            );

            return $version;
        });
    }

    /**
     * Save approved food substitution selections or request a revision on a delivered diet plan.
     *
     * @param  array<string, string>  $substitutionOverrides
     */
    public function updateCustomerSubstitutionsAndRevision(
        User $user,
        PersonalisedPlanRequest $planRequest,
        array $substitutionOverrides = [],
        ?string $revisionNotes = null
    ): PersonalisedPlanRequest {
        if ($planRequest->user_id !== $user->id && ! $user->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            throw new AuthorizationException('You may only update your own diet plan preferences.');
        }

        $updates = [
            'custom_substitution_overrides' => $substitutionOverrides,
        ];

        if ($revisionNotes !== null && trim($revisionNotes) !== '') {
            $updates['customer_revision_notes'] = trim($revisionNotes);
            $updates['status'] = PersonalisedPlanRequest::STATUS_REVISION_REQUESTED;
        }

        $planRequest->update($updates);

        $this->audit->record(
            action: isset($updates['status']) ? 'diet.revision.requested' : 'diet.substitutions.updated',
            subject: $planRequest,
            oldValues: null,
            newValues: $updates,
            actor: $user,
        );

        return $planRequest;
    }

    /**
     * @return list<string>
     */
    private function detectClinicalKeywords(string $haystack): array
    {
        $normalized = strtolower($haystack);
        $matched = [];

        foreach (self::CLINICAL_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                $matched[] = $keyword;
            }
        }

        return array_values(array_unique($matched));
    }

    private function authorizeClientAccess(User $actor, int $clientId, string $permission): void
    {
        if ($actor->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            return;
        }

        if (! $actor->can($permission)) {
            throw new AuthorizationException("Missing required permission: {$permission}");
        }

        if (! $actor->isAssignedToClient($clientId)) {
            throw new AuthorizationException('You are not assigned to this coaching/diet client.');
        }
    }
}
