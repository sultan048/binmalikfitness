<?php

namespace App\Policies;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    public function update(User $actor, Address $address): bool
    {
        return $actor->id === $address->user_id || $actor->hasRole(RolePermissionMatrix::ROLE_ADMIN);
    }

    public function delete(User $actor, Address $address): bool
    {
        return $actor->id === $address->user_id || $actor->hasRole(RolePermissionMatrix::ROLE_ADMIN);
    }
}
