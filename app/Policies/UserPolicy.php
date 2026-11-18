<?php

namespace App\Policies;

use App\Models\User;

/**
 * Otorisasi CRUD pengguna (MOD-10, AC-10-4).
 *
 * Hanya Owner (administrator sistem) yang memegang permission
 * `users.view` dan `users.manage` sesuai tabel aktor MOD-10.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }

    public function toggleActive(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }
}
