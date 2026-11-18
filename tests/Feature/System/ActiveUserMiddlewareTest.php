<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActiveUserMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_allows_an_active_user_to_open_a_protected_page(): void
    {
        $user = User::factory()->forRole(UserRole::Owner)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    #[Test]
    public function it_logs_out_and_redirects_an_inactive_user_with_an_error_message(): void
    {
        $user = User::factory()->forRole(UserRole::Owner)->inactive()->create();

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Akun Anda tidak aktif. Hubungi administrator.');

        $this->assertGuest();
    }

    #[Test]
    public function it_returns_403_json_for_an_inactive_user_on_a_json_request(): void
    {
        $user = User::factory()->forRole(UserRole::Owner)->inactive()->create();

        $this->actingAs($user)
            ->getJson(route('dashboard'))
            ->assertForbidden()
            ->assertJson(['message' => 'Akun Anda tidak aktif.']);
    }

    #[Test]
    public function it_does_not_affect_guest_routes(): void
    {
        $this->get(route('login'))->assertOk();
    }
}
