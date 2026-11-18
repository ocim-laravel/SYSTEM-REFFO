<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Login');
    }

    public function test_active_admin_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
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

    public function test_active_operator_can_login_and_is_redirected_to_operator_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@example.com',
            'role' => 'operator',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'operator@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('operator.dashboard'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'role' => 'admin',
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
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
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
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
