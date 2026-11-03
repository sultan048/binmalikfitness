<?php

namespace App\Domain\Identity;

use App\Domain\Platform\AuditLogger;
use App\Models\CoachAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CoachAssignmentService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function assign(
        User $actor,
        User $coach,
        User $client,
        string $context = 'coaching',
        ?string $notes = null,
    ): CoachAssignment {
        if (! $coach->hasAnyRole([RolePermissionMatrix::ROLE_COACH, RolePermissionMatrix::ROLE_MANAGER, RolePermissionMatrix::ROLE_ADMIN])) {
            throw new InvalidArgumentException('Selected user does not hold coach capability.');
        }

        return DB::transaction(function () use ($actor, $coach, $client, $context, $notes) {
            // End any currently active assignment for the same client + context
            CoachAssignment::query()
                ->where('client_id', $client->id)
                ->where('context', $context)
                ->whereNull('ends_at')
                ->update(['ends_at' => now()]);

            $assignment = CoachAssignment::create([
                'coach_id' => $coach->id,
                'client_id' => $client->id,
                'context' => $context,
                'assigned_by' => $actor->id,
                'starts_at' => now(),
                'notes' => $notes,
            ]);

            $this->audit->record(
                action: 'coaching.assignment.created',
                subject: $assignment,
                oldValues: null,
                newValues: [
                    'coach_id' => $coach->id,
                    'client_id' => $client->id,
                    'context' => $context,
                ],
                actor: $actor,
            );

            return $assignment;
        });
    }
}
