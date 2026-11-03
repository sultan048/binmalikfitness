<?php

namespace App\Domain\Platform;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\CoachingSubscription;
use App\Models\DietPlan;
use App\Models\DietPlanVersion;
use App\Models\Entitlement;
use App\Models\User;
use App\Models\Video;
use App\Models\WorkoutAssignment;
use App\Models\WorkoutProgram;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

class EntitlementService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function grant(
        User $user,
        Model $entitleable,
        Model $source,
        ?CarbonInterface $startsAt = null,
        ?CarbonInterface $endsAt = null,
        ?User $actor = null
    ): Entitlement {
        $entitlement = Entitlement::updateOrCreate(
            [
                'user_id' => $user->id,
                'entitleable_type' => $entitleable::class,
                'entitleable_id' => $entitleable->getKey(),
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
            ],
            [
                'status' => Entitlement::STATUS_ACTIVE,
                'starts_at' => $startsAt ?? now(),
                'ends_at' => $endsAt,
                'revoked_at' => null,
                'revoked_by' => null,
                'revoke_reason' => null,
            ]
        );

        $this->audit->record(
            action: 'entitlement.granted',
            subject: $entitlement,
            oldValues: null,
            newValues: [
                'user_id' => $user->id,
                'entitleable_type' => class_basename($entitleable),
                'entitleable_id' => $entitleable->getKey(),
                'source_type' => class_basename($source),
                'source_id' => $source->getKey(),
                'ends_at' => $endsAt?->toIso8601String(),
            ],
            actor: $actor ?? $user,
        );

        return $entitlement;
    }

    public function revoke(Entitlement $entitlement, User $actor, string $reason): Entitlement
    {
        $entitlement->update([
            'status' => Entitlement::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
            'revoke_reason' => $reason,
        ]);

        $this->audit->record(
            action: 'entitlement.revoked',
            subject: $entitlement,
            oldValues: ['status' => Entitlement::STATUS_ACTIVE],
            newValues: ['status' => Entitlement::STATUS_REVOKED, 'reason' => $reason],
            actor: $actor,
        );

        return $entitlement;
    }

    public function revokeBySource(Model $source, User $actor, string $reason): int
    {
        $entitlements = Entitlement::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->where('status', Entitlement::STATUS_ACTIVE)
            ->get();

        foreach ($entitlements as $entitlement) {
            $this->revoke($entitlement, $actor, $reason);
        }

        return $entitlements->count();
    }

    public function allows(?User $user, Model $entitleable): bool
    {
        if ($entitleable instanceof Video && $entitleable->access === 'public') {
            return true;
        }

        if ($entitleable instanceof DietPlan && $entitleable->type === 'free') {
            return true;
        }

        if (! $user || ! $user->isActive()) {
            return false;
        }

        if ($user->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            return true;
        }

        // Direct active entitlement check
        $hasDirectEntitlement = Entitlement::query()
            ->where('user_id', $user->id)
            ->where('entitleable_type', $entitleable::class)
            ->where('entitleable_id', $entitleable->getKey())
            ->where('status', Entitlement::STATUS_ACTIVE)
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->exists();

        if ($hasDirectEntitlement) {
            return true;
        }

        // Entitled video access via active coaching subscription that includes video library
        if ($entitleable instanceof Video && $entitleable->access === 'entitled') {
            if ($user->isPrivilegedStaff()) {
                return true;
            }

            return CoachingSubscription::query()
                ->where('user_id', $user->id)
                ->where('status', CoachingSubscription::STATUS_ACTIVE)
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->whereHas('package', fn ($q) => $q->where('includes_video_library', true))
                ->exists();
        }

        // DietPlanVersion access via parent DietPlan entitlement or assigned coach
        if ($entitleable instanceof DietPlanVersion) {
            if ($entitleable->user_id !== null && $user->isAssignedToClient($entitleable->user_id)) {
                return true;
            }

            if ($entitleable->user_id === null || $entitleable->user_id === $user->id) {
                return $this->allows($user, $entitleable->dietPlan);
            }

            return false;
        }

        // WorkoutProgram access via active WorkoutAssignment
        if ($entitleable instanceof WorkoutProgram) {
            return WorkoutAssignment::query()
                ->where('user_id', $user->id)
                ->where('workout_program_id', $entitleable->id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    /**
     * Issue a signed playback descriptor for a video after strict server-side entitlement verification.
     *
     * @return array{video_slug: string, access: string, provider: string, provider_ref: string, signed_token: string, expires_at: string}
     */
    public function issueVideoPlaybackToken(?User $user, Video $video): array
    {
        if (! $this->allows($user, $video)) {
            throw new AuthorizationException('You do not have an active entitlement to stream this training video.');
        }

        $expiresAt = now()->addMinutes(30);
        $subjectId = $user?->id ?? 0;
        $payload = "{$video->id}|{$video->slug}|{$subjectId}|{$expiresAt->timestamp}";
        $signature = hash_hmac('sha256', $payload, (string) config('app.key'));

        if ($user && $video->access === 'entitled') {
            $this->audit->record(
                action: 'media.entitled_video.stream_authorized',
                subject: $video,
                oldValues: null,
                newValues: ['video_slug' => $video->slug, 'expires_at' => $expiresAt->toIso8601String()],
                actor: $user,
            );
        }

        return [
            'video_slug' => $video->slug,
            'access' => $video->access,
            'provider' => $video->provider,
            'provider_ref' => $video->provider_ref ?? 'bmf-stream-'.$video->slug,
            'signed_token' => $signature,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
