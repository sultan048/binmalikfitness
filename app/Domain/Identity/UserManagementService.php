<?php

namespace App\Domain\Identity;

use App\Domain\Platform\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserManagementService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<string>  $roles
     */
    public function syncUserRoles(User $actor, User $target, array $roles): void
    {
        if ($actor->id === $target->id) {
            throw new InvalidArgumentException('You cannot modify your own role.');
        }

        $allowedRoles = array_keys(RolePermissionMatrix::roleMap());
        foreach ($roles as $role) {
            if (! in_array($role, $allowedRoles, true)) {
                throw new InvalidArgumentException("Invalid role: {$role}");
            }
        }

        DB::transaction(function () use ($actor, $target, $roles) {
            $oldRoles = $target->getRoleNames()->values()->all();
            $target->syncRoles($roles);

            $this->audit->record(
                action: 'identity.user.roles_updated',
                subject: $target,
                oldValues: ['roles' => $oldRoles],
                newValues: ['roles' => array_values($roles)],
                actor: $actor,
            );
        });
    }

    public function setAccountStatus(User $actor, User $target, string $status): void
    {
        if (! in_array($status, ['active', 'deactivated'], true)) {
            throw new InvalidArgumentException('Invalid account status.');
        }

        if ($actor->id === $target->id) {
            throw new InvalidArgumentException('You cannot deactivate your own account.');
        }

        DB::transaction(function () use ($actor, $target, $status) {
            $oldStatus = $target->status;
            $target->update(['status' => $status]);

            if ($status === 'deactivated') {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }

            $this->audit->record(
                action: 'identity.user.status_changed',
                subject: $target,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $status],
                actor: $actor,
            );
        });
    }
}
