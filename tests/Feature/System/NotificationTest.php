<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\System\NotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private NotificationService $notifications;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->user = User::factory()->forRole(UserRole::Owner)->create();
        $this->notifications = app(NotificationService::class);
    }

    #[Test]
    public function notification_list_shows_only_the_current_users_notifications(): void
    {
        $mine = $this->notifications->create($this->user, 'system', 'Notifikasi saya', 'Isi notifikasi saya.');
        $otherUser = User::factory()->forRole(UserRole::Admin)->create();
        $theirs = $this->notifications->create($otherUser, 'system', 'Notifikasi orang lain', 'Isi rahasia.');

        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($mine->title)
            ->assertSee($mine->body)
            ->assertDontSee($theirs->title)
            ->assertDontSee($theirs->body);
    }

    #[Test]
    public function user_can_mark_their_notification_as_read(): void
    {
        $notification = $this->notifications->create($this->user, 'system', 'Notifikasi baru');

        $this->assertSame(1, $this->notifications->unreadCount($this->user));

        $this->actingAs($this->user)
            ->patch(route('notifications.update', $notification))
            ->assertRedirect()
            ->assertSessionHas('status', 'Notifikasi ditandai sudah dibaca.');

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(0, $this->notifications->unreadCount($this->user));
    }

    #[Test]
    public function user_can_mark_all_their_notifications_as_read(): void
    {
        $this->notifications->create($this->user, 'system', 'Pertama');
        $this->notifications->create($this->user, 'system', 'Kedua');
        $otherUser = User::factory()->forRole(UserRole::Admin)->create();
        $otherNotification = $this->notifications->create($otherUser, 'system', 'Orang lain');

        $this->actingAs($this->user)
            ->patch(route('notifications.read-all'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Semua notifikasi ditandai sudah dibaca.');

        $this->assertSame(0, $this->notifications->unreadCount($this->user));
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    #[Test]
    public function user_cannot_mark_another_users_notification_as_read(): void
    {
        $otherUser = User::factory()->forRole(UserRole::Admin)->create();
        $notification = $this->notifications->create($otherUser, 'system', 'Notifikasi orang lain');

        $this->actingAs($this->user)
            ->patch(route('notifications.update', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    #[Test]
    public function layout_dropdown_shows_unread_count_and_latest_notifications(): void
    {
        $this->notifications->create($this->user, 'system', 'Notifikasi terbaru', 'Isi singkat.');
        $this->notifications->create($this->user, 'system', 'Notifikasi kedua');

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Notifikasi', false)
            ->assertSee('Notifikasi terbaru', false)
            ->assertSee('Lihat semua notifikasi', false)
            ->assertSee('notification-badge', false);
    }

    #[Test]
    public function it_hides_the_badge_when_there_are_no_unread_notifications(): void
    {
        $notification = $this->notifications->create($this->user, 'system', 'Sudah dibaca');
        $this->notifications->markAsRead($notification);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('notification-badge', false);
    }

    #[Test]
    public function service_limits_dropdown_items_but_lists_all_on_index(): void
    {
        foreach (range(1, 7) as $i) {
            $this->notifications->create($this->user, 'system', 'Notifikasi '.$i);
        }

        $this->assertCount(5, $this->notifications->forUser($this->user, 5));
        $this->assertCount(7, $this->notifications->allFor($this->user));
        $this->assertSame(7, $this->notifications->unreadCount($this->user));
    }

    #[Test]
    public function mark_all_as_read_is_idempotent(): void
    {
        $this->notifications->create($this->user, 'system', 'Pertama');

        $this->assertSame(1, $this->notifications->markAllAsRead($this->user));
        $this->assertSame(0, $this->notifications->markAllAsRead($this->user));
    }
}
