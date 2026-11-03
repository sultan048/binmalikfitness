<?php

namespace App\Policies;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the actor can view confidential health/coaching records of the target user.
     */
    public function viewConfidential(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        if ($actor->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            return true;
        }

        if ($actor->can('health.view') && $actor->isAssignedToClient($target->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the actor can change roles on the target user.
     */
    public function manageRoles(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        return $actor->can('roles.manage');
    }

    /**
     * Determine whether the actor can activate or deactivate the target user.
     * Managers may only toggle non-staff customer accounts, never Admin/Manager/Coach accounts.
     */
    public function toggleStatus(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            return true;
        }

        if ($actor->can('users.manage') && ! $target->isPrivilegedStaff()) {
            return true;
        }

        return false;
    }
}
