<?php

namespace App\Policies;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->hasAnyRole([RolePermissionMatrix::ROLE_ADMIN, RolePermissionMatrix::ROLE_MANAGER])) {
            return true;
        }

        return $order->user_id !== null && $order->user_id === $user->id;
    }

    public function manageFulfilment(User $user, Order $order): bool
    {
        return $user->can('orders.fulfil');
    }

    public function verifyPayment(User $user, Order $order): bool
    {
        return $user->can('payments.verify');
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->hasRole(RolePermissionMatrix::ROLE_ADMIN) && $user->can('refunds.approve');
    }
}
