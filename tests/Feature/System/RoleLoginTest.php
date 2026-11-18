<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_redirects_owner_to_the_generic_dashboard_after_login(): void
    {
        $owner = User::factory()->forRole(UserRole::Owner)->create([
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($owner);
        $response->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function it_redirects_finance_to_the_generic_dashboard_after_login(): void
    {
        $finance = User::factory()->forRole(UserRole::Finance)->create([
            'email' => 'finance@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'finance@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($finance);
        $response->assertRedirect(route('dashboard'));
    }
}
