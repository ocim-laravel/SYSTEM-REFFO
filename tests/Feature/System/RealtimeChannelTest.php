<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Events\RealtimeNotification;
use App\Models\Division;
use App\Models\User;
use App\Services\System\RealtimeService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AC-10-6: kanal privat realtime hanya bisa didengar user berwenang.
 *
 * Memakai DatabaseMigrations (bukan RefreshDatabase) karena callback
 * after-commit baru dijalankan saat level transaksi kembali ke nol.
 */
class RealtimeChannelTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DivisionSeeder::class);
    }

    #[Test]
    public function it_allows_user_to_listen_to_own_user_channel(): void
    {
        $user = $this->userIn(UserRole::Admin, 'ADM');

        $this->authChannel($user, 'user.'.$user->id)->assertOk()->assertJsonStructure(['auth']);
    }

    #[Test]
    public function it_forbids_user_from_listening_to_other_user_channel(): void
    {
        $user = $this->userIn(UserRole::Admin, 'ADM');
        $other = $this->userIn(UserRole::Admin, 'ADM');

        $this->authChannel($user, 'user.'.$other->id)->assertForbidden();
    }

    #[Test]
    public function it_allows_user_to_listen_to_own_role_channel(): void
    {
        $user = $this->userIn(UserRole::Designer, 'DSN');

        $this->authChannel($user, 'role.designer')->assertOk()->assertJsonStructure(['auth']);
    }

    #[Test]
    public function it_forbids_user_from_listening_to_other_role_channel(): void
    {
        $user = $this->userIn(UserRole::Designer, 'DSN');

        $this->authChannel($user, 'role.finance')->assertForbidden();
    }

    #[Test]
    public function it_allows_user_to_listen_to_own_division_channel(): void
    {
        $user = $this->userIn(UserRole::Production, 'PRD');

        $this->authChannel($user, 'division.prd')->assertOk()->assertJsonStructure(['auth']);
    }

    #[Test]
    public function it_forbids_user_from_listening_to_other_division_channel(): void
    {
        $user = $this->userIn(UserRole::Production, 'PRD');

        $this->authChannel($user, 'division.hrd')->assertForbidden();
    }

    #[Test]
    public function it_forbids_user_with_wrong_role_even_when_division_matches(): void
    {
        $user = $this->userIn(UserRole::Hrd, 'ADM');

        $this->authChannel($user, 'division.adm')->assertForbidden();
    }

    #[Test]
    public function it_allows_owner_to_listen_to_role_and_division_channels(): void
    {
        $owner = User::factory()->forRole(UserRole::Owner)->create();

        $this->authChannel($owner, 'role.production')->assertOk()->assertJsonStructure(['auth']);
        $this->authChannel($owner, 'division.keu')->assertOk()->assertJsonStructure(['auth']);
    }

    #[Test]
    public function it_broadcasts_test_event_only_to_private_channels(): void
    {
        $user = $this->userIn(UserRole::Admin, 'ADM');

        $event = app(RealtimeService::class)->sendTestNotification($user, 'halo');

        $channels = $event->broadcastOn();
        $this->assertNotEmpty($channels);

        foreach ($channels as $channel) {
            $this->assertInstanceOf(PrivateChannel::class, $channel);
        }

        $this->assertSame([
            'private-user.'.$user->id,
            'private-role.admin',
            'private-division.adm',
        ], array_map(fn (PrivateChannel $channel): string => (string) $channel, $channels));
    }

    #[Test]
    public function it_keeps_test_payload_free_of_sensitive_data(): void
    {
        $user = $this->userIn(UserRole::Finance, 'KEU');

        $payload = app(RealtimeService::class)
            ->sendTestNotification($user, 'halo')
            ->broadcastWith();

        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayHasKey('sent_at', $payload);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('price', $payload);
    }

    #[Test]
    public function it_dispatches_test_event_only_after_transaction_commit(): void
    {
        Event::fake([RealtimeNotification::class]);
        $user = $this->userIn(UserRole::Admin, 'ADM');

        DB::beginTransaction();
        app(RealtimeService::class)->sendTestNotification($user, 'halo');

        Event::assertNotDispatched(RealtimeNotification::class);

        DB::commit();

        Event::assertDispatched(RealtimeNotification::class, 1);
    }

    #[Test]
    public function it_does_not_dispatch_test_event_when_transaction_rolls_back(): void
    {
        Event::fake([RealtimeNotification::class]);
        $user = $this->userIn(UserRole::Admin, 'ADM');

        DB::beginTransaction();
        app(RealtimeService::class)->sendTestNotification($user, 'halo');
        DB::rollBack();

        Event::assertNotDispatched(RealtimeNotification::class);
    }

    #[Test]
    public function it_rejects_test_payload_larger_than_four_kilobytes(): void
    {
        $user = $this->userIn(UserRole::Admin, 'ADM');

        $this->expectException(InvalidArgumentException::class);

        app(RealtimeService::class)->sendTestNotification($user, str_repeat('a', 5000));
    }

    #[Test]
    public function it_dispatches_test_event_from_broadcast_test_endpoint(): void
    {
        Event::fake([RealtimeNotification::class]);
        $user = $this->userIn(UserRole::Admin, 'ADM');

        $this->actingAs($user)
            ->get(route('broadcast-test', ['message' => 'halo dari endpoint']))
            ->assertOk()
            ->assertJsonPath('ok', true);

        Event::assertDispatched(
            RealtimeNotification::class,
            fn (RealtimeNotification $event): bool => $event->message === 'halo dari endpoint',
        );
    }

    #[Test]
    public function it_requires_login_to_trigger_the_test_event(): void
    {
        $this->get(route('broadcast-test'))->assertRedirect(route('login'));
    }

    private function authChannel(User $user, string $channel): TestResponse
    {
        // Saat boot, kanal terdaftar di broadcaster "null" (default testing).
        // Daftarkan ulang di broadcaster reverb agar /broadcasting/auth
        // benar-benar mengevaluasi callback otorisasi kanal.
        config()->set('broadcasting.default', 'reverb');
        Broadcast::purge();
        require base_path('routes/channels.php');

        return $this->actingAs($user)->postJson('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-'.$channel,
        ]);
    }

    private function userIn(UserRole $role, string $divisionCode): User
    {
        return User::factory()
            ->forRole($role)
            ->division(Division::query()->where('code', $divisionCode)->firstOrFail())
            ->create();
    }
}
