<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_forces_password_change_when_user_must_change_password(): void
    {
        $user = User::factory()->forRole(UserRole::Admin)->create([
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('password.change'));
    }

    #[Test]
    public function it_allows_flagged_user_to_open_change_password_page(): void
    {
        $user = User::factory()->forRole(UserRole::Admin)->create([
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get(route('password.change'));

        $response->assertOk();
        $response->assertSee('Ubah Password');
    }

    #[Test]
    public function it_changes_password_and_clears_flag_when_current_password_matches(): void
    {
        $user = User::factory()->forRole(UserRole::Admin)->create([
            'password' => 'oldpassword',
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $user->refresh();

        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse($user->must_change_password);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    #[Test]
    public function it_rejects_password_change_when_current_password_is_wrong(): void
    {
        $user = User::factory()->forRole(UserRole::Admin)->create([
            'password' => 'oldpassword',
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'wrong-password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('oldpassword', $user->fresh()->password));
    }

    #[Test]
    public function it_rejects_new_password_shorter_than_eight_characters(): void
    {
        $user = User::factory()->forRole(UserRole::Admin)->create([
            'password' => 'oldpassword',
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->put(route('password.change.update'), [
            'current_password' => 'oldpassword',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->must_change_password);
    }
}
