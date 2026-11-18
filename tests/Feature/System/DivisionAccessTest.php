<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DivisionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_forbids_admin_when_opening_divisions_page(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('system.divisions.index'))
            ->assertForbidden();
    }

    #[Test]
    public function it_forbids_finance_when_creating_a_division(): void
    {
        $finance = User::factory()->forRole(UserRole::Finance)->create();

        $this->actingAs($finance)
            ->post(route('system.divisions.store'), [
                'code' => 'OPS',
                'name' => 'Operasional',
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('divisions', ['code' => 'OPS']);
    }

    #[Test]
    public function it_forbids_designer_when_opening_create_division_form(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $this->actingAs($designer)
            ->get(route('system.divisions.create'))
            ->assertForbidden();
    }

    #[Test]
    public function it_allows_owner_to_open_divisions_page(): void
    {
        $owner = User::factory()->forRole(UserRole::Owner)->create();

        $this->actingAs($owner)
            ->get(route('system.divisions.index'))
            ->assertOk();
    }

    #[Test]
    public function it_redirects_guest_to_login_when_opening_divisions_page(): void
    {
        $this->get(route('system.divisions.index'))
            ->assertRedirect(route('login'));
    }
}
