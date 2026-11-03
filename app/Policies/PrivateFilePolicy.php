<?php

namespace App\Policies;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\PrivateFile;
use App\Models\User;

class PrivateFilePolicy
{
    public function view(User $actor, PrivateFile $file): bool
    {
        if ($actor->id === $file->owner_id) {
            return true;
        }

        if ($actor->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            return true;
        }

        if ($file->category === 'payment_receipt' && $actor->can('payments.verify')) {
            return true;
        }

        if ($file->is_confidential_health) {
            return $actor->can('health.view') && $actor->isAssignedToClient($file->owner_id);
        }

        return $actor->can('coaching.deliver') && $actor->isAssignedToClient($file->owner_id);
    }
}
