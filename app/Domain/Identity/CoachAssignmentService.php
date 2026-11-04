<?php

namespace App\Domain\Identity;

use App\Domain\Platform\AuditLogger;
use App\Models\CoachAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CoachAssignmentService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Admin-created (or system-created, $preApproved) assignments take effect immediately.
     * Any other staff member's assignment is created as pending until an admin approves it,
     * because an assignment unlocks the client's confidential health records.
     */
    public function assign(
        User $actor,
        User $coach,
        User $client,
        string $context = 'coaching',
        ?string $notes = null,
        bool $preApproved = false,
    ): CoachAssignment {
        if (! $coach->hasAnyRole([RolePermissionMatrix::ROLE_COACH, RolePermissionMatrix::ROLE_MANAGER, RolePermissionMatrix::ROLE_ADMIN])) {
            throw new InvalidArgumentException('Selected user does not hold coach capability.');
        }

        $approved = $preApproved || $actor->hasRole(RolePermissionMatrix::ROLE_ADMIN);

        return DB::transaction(function () use ($actor, $coach, $client, $context, $notes, $approved) {
            if ($approved) {
                $this->endActiveAssignments($client, $context);
            }

            $assignment = CoachAssignment::create([
                'coach_id' => $coach->id,
                'client_id' => $client->id,
                'context' => $context,
                'status' => $approved ? CoachAssignment::STATUS_APPROVED : CoachAssignment::STATUS_PENDING,
                'approved_by' => $approved ? $actor->id : null,
                'approved_at' => $approved ? now() : null,
                'assigned_by' => $actor->id,
                'starts_at' => now(),
                'notes' => $notes,
            ]);

            $this->audit->record(
                action: $approved ? 'coaching.assignment.created' : 'coaching.assignment.requested',
                subject: $assignment,
                oldValues: null,
                newValues: [
                    'coach_id' => $coach->id,
                    'client_id' => $client->id,
                    'context' => $context,
                    'status' => $assignment->status,
                ],
                actor: $actor,
            );

            return $assignment;
        });
    }

    public function approve(CoachAssignment $assignment, User $admin): CoachAssignment
    {
        $this->guardPending($assignment);

        return DB::transaction(function () use ($assignment, $admin) {
            $this->endActiveAssignments($assignment->client, $assignment->context);

            $assignment->update([
                'status' => CoachAssignment::STATUS_APPROVED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'starts_at' => now(),
            ]);

            $this->audit->record('coaching.assignment.approved', $assignment, ['status' => CoachAssignment::STATUS_PENDING], ['status' => CoachAssignment::STATUS_APPROVED], $admin);

            return $assignment->fresh();
        });
    }

    public function reject(CoachAssignment $assignment, User $admin): CoachAssignment
    {
        $this->guardPending($assignment);

        $assignment->update([
            'status' => CoachAssignment::STATUS_REJECTED,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'ends_at' => now(),
        ]);

        $this->audit->record('coaching.assignment.rejected', $assignment, ['status' => CoachAssignment::STATUS_PENDING], ['status' => CoachAssignment::STATUS_REJECTED], $admin);

        return $assignment->fresh();
    }

    private function endActiveAssignments(User $client, string $context): void
    {
        CoachAssignment::query()
            ->approved()
            ->where('client_id', $client->id)
            ->where('context', $context)
            ->whereNull('ends_at')
            ->update(['ends_at' => now()]);
    }

    private function guardPending(CoachAssignment $assignment): void
    {
        if ($assignment->status !== CoachAssignment::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'assignment' => ['Only pending assignments can be approved or rejected.'],
            ]);
        }
    }
}
