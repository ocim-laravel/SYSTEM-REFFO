<?php

namespace App\Policies;

use App\Models\User;

/**
 * Hanya Owner yang dapat melihat dan mengubah pengaturan sistem (MOD-10).
 */
class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function update(User $user): bool
    {
        return $user->can('settings.manage');
    }
}
