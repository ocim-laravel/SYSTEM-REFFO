<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

/**
 * Pengguna hanya dapat membaca dan menandai notifikasinya sendiri (MOD-10).
 */
class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    public function update(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}
