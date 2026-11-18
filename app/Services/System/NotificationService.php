<?php

namespace App\Services\System;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Notifikasi internal yang tampil di layout (MOD-10, PH-00).
 */
class NotificationService
{
    /**
     * @return Collection<int, Notification>
     */
    public function forUser(User $user, int $limit = 5): Collection
    {
        return Notification::query()->forUser($user)->latest()->limit($limit)->get();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function allFor(User $user): Collection
    {
        return Notification::query()->forUser($user)->latest()->get();
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()->forUser($user)->unread()->count();
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function create(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
    ): Notification {
        return Notification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public function markAsRead(Notification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::query()->forUser($user)->unread()->update(['read_at' => now()]);
    }
}
