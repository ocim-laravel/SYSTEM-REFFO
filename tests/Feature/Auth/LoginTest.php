<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Login');
    }

    public function test_active_admin_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->forRole(UserRole::Admin)->create([
            'email' => 'admin@example.com',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_active_production_user_can_login_and_is_redirected_to_generic_dashboard(): void
    {
        $user = User::factory()->forRole(UserRole::Production)->create([
            'email' => 'production@example.com',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'production@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_without_operator_role_is_forbidden_from_operator_dashboard(): void
    {
        $user = User::factory()->forRole(UserRole::Production)->create();

        $this->actingAs($user)
            ->get(route('operator.dashboard'))
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->forRole(UserRole::Admin)->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
            'password' => 'secret123',
        ]);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_user_with_invalid_credentials_cannot_login(): void
    {
        User::factory()->forRole(UserRole::Admin)->create([
            'email' => 'admin@example.com',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->forRole(UserRole::Admin)->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
