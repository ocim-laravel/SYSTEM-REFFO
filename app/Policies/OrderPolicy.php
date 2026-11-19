<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Otorisasi order (MOD-02).
 *
 * Lihat: Admin, Owner, Designer. Validasi: Admin saja (BR-03).
 * Ubah harga/diskon: Admin, Owner.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.manage');
    }

    public function validate(User $user, Order $order): bool
    {
        return $user->can('orders.validate');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->can('orders.manage');
    }
}
