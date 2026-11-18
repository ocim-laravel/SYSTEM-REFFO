<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->forRole(UserRole::Owner)->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@reffo.test',
            'role' => UserRole::Designer->value,
            'division_id' => null,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    #[Test]
    public function it_creates_a_user_with_forced_password_change_and_writes_an_audit_log(): void
    {
        $this->actingAs($this->owner)
            ->post(route('system.users.store'), $this->payload())
            ->assertRedirect(route('system.users.index'));

        $user = User::query()->where('email', 'budi@reffo.test')->firstOrFail();

        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole(UserRole::Designer->value));

        $log = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('user.created', $log->action);
        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(User::class, $log->model_type);
        $this->assertSame($user->id, $log->model_id);
        $this->assertNull($log->before);
        $this->assertSame('budi@reffo.test', $log->after['email']);
        $this->assertArrayNotHasKey('password', $log->after);
    }

    #[Test]
    public function it_writes_before_and_after_when_updating_a_user(): void
    {
        $target = User::factory()->forRole(UserRole::Designer)->create([
            'name' => 'Nama Lama',
            'email' => 'lama@reffo.test',
        ]);

        $this->actingAs($this->owner)
            ->put(route('system.users.update', $target), [
                'name' => 'Nama Baru',
                'email' => 'BARU@reffo.test',
                'role' => UserRole::Finance->value,
                'division_id' => null,
                'is_active' => '0',
            ])
            ->assertRedirect(route('system.users.index'));

        $target->refresh();

        $this->assertSame('baru@reffo.test', $target->email);
        $this->assertSame('Nama Baru', $target->name);
        $this->assertFalse($target->is_active);

        $log = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('user.updated', $log->action);
        $this->assertSame($target->id, $log->model_id);
        $this->assertSame('Nama Lama', $log->before['name']);
        $this->assertSame('Nama Baru', $log->after['name']);
        $this->assertSame(UserRole::Designer->value, $log->before['role']);
        $this->assertSame(UserRole::Finance->value, $log->after['role']);
        $this->assertTrue($log->before['is_active']);
        $this->assertFalse($log->after['is_active']);
    }

    #[Test]
    public function it_writes_a_before_snapshot_when_deleting_a_user(): void
    {
        $target = User::factory()->create(['email' => 'hapus@reffo.test']);

        $this->actingAs($this->owner)
            ->delete(route('system.users.destroy', $target))
            ->assertRedirect(route('system.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);

        $log = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('user.deleted', $log->action);
        $this->assertSame($target->id, $log->model_id);
        $this->assertSame('hapus@reffo.test', $log->before['email']);
        $this->assertNull($log->after);
    }

    #[Test]
    public function it_deactivates_a_user_and_logs_the_status_change(): void
    {
        $target = User::factory()->create([
            'email' => 'nonaktif@reffo.test',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('system.users.toggle-active', $target))
            ->assertRedirect(route('system.users.index'));

        $this->assertFalse($target->refresh()->is_active);

        $log = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('user.updated', $log->action);
        $this->assertTrue($log->before['is_active']);
        $this->assertFalse($log->after['is_active']);
        $this->assertSame('Pengguna dinonaktifkan.', $log->reason);
    }

    #[Test]
    public function it_resets_a_password_and_requires_a_change_on_next_login(): void
    {
        $target = User::factory()->create([
            'email' => 'reset@reffo.test',
            'password' => 'password',
            'must_change_password' => false,
        ]);

        $this->actingAs($this->owner)
            ->put(route('system.users.reset-password', $target), [
                'password' => 'passwordbaru',
                'password_confirmation' => 'passwordbaru',
            ])
            ->assertRedirect(route('system.users.index'));

        $target->refresh();

        $this->assertTrue(Hash::check('passwordbaru', $target->password));
        $this->assertTrue($target->must_change_password);

        $log = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('user.password_reset', $log->action);
        $this->assertFalse($log->before['must_change_password']);
        $this->assertTrue($log->after['must_change_password']);
        $this->assertStringNotContainsString('passwordbaru', json_encode([$log->before, $log->after]));
    }

    #[Test]
    public function it_forbids_admin_when_opening_the_users_page(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('system.users.index'))
            ->assertForbidden();
    }

    #[Test]
    public function it_forbids_admin_when_creating_a_user(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->post(route('system.users.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'budi@reffo.test']);
    }

    #[Test]
    public function it_redirects_a_guest_to_login_when_opening_the_users_page(): void
    {
        $this->get(route('system.users.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_lists_users_and_shows_the_menu_for_owner(): void
    {
        User::factory()->create([
            'name' => 'Siti Aminah',
            'email' => 'siti@reffo.test',
        ]);

        $this->actingAs($this->owner)
            ->get(route('system.users.index'))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('data-menu="users"', false);
    }

    #[Test]
    public function it_blocks_login_when_the_user_is_inactive(): void
    {
        User::factory()->inactive()->create([
            'email' => 'nonaktif@reffo.test',
            'password' => 'rahasia123',
        ]);

        $this->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => 'nonaktif@reffo.test',
                'password' => 'rahasia123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('nonaktif', session('errors')->first('email'));
        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[Test]
    public function it_blocks_login_after_owner_deactivates_the_user(): void
    {
        $target = User::factory()->create([
            'email' => 'duluaktif@reffo.test',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('system.users.toggle-active', $target))
            ->assertRedirect(route('system.users.index'));

        $this->post(route('logout'));

        $this->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => 'duluaktif@reffo.test',
                'password' => 'rahasia123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function it_redirects_a_newly_created_user_to_change_password_when_logging_in(): void
    {
        $this->actingAs($this->owner)
            ->post(route('system.users.store'), $this->payload())
            ->assertRedirect(route('system.users.index'));

        $this->post(route('logout'));

        $this->post(route('login.attempt'), [
            'email' => 'budi@reffo.test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('password.change'));

        $this->assertAuthenticated();
    }
}
