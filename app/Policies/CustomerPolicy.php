<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Otorisasi pelanggan (MOD-01).
 *
 * Lihat: Admin, Admin Produksi, Finance, Owner.
 * Tambah/ubah/nonaktifkan: Admin, Owner.
 * Hapus permanen: Owner saja (dengan alasan, BR-17/BR-18).
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customers.manage');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.manage');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }
}
