<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SidebarMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @param  list<string>  $visible
     * @param  list<string>  $hidden
     */
    private function assertMenuFor(UserRole $role, array $visible, array $hidden): TestResponse
    {
        $user = User::factory()->forRole($role)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        foreach ($visible as $key) {
            $response->assertSee('data-menu="'.$key.'"', false);
        }

        foreach ($hidden as $key) {
            $response->assertDontSee('data-menu="'.$key.'"', false);
        }

        return $response;
    }

    #[Test]
    public function owner_sees_every_menu(): void
    {
        $this->assertMenuFor(
            UserRole::Owner,
            ['dashboard', 'customer', 'order', 'design', 'production', 'finishing', 'delivery', 'finance', 'hrd', 'material', 'reports', 'divisions', 'users', 'work-calendars'],
            [],
        );
    }

    #[Test]
    public function admin_sees_customer_order_and_production_menus(): void
    {
        $this->assertMenuFor(
            UserRole::Admin,
            ['dashboard', 'customer', 'order', 'production'],
            ['design', 'finance', 'hrd', 'delivery', 'reports', 'divisions'],
        );
    }

    #[Test]
    public function designer_only_sees_order_and_design_menus(): void
    {
        $this->assertMenuFor(
            UserRole::Designer,
            ['dashboard', 'order', 'design'],
            ['customer', 'production', 'finishing', 'finance', 'divisions'],
        );
    }

    #[Test]
    public function production_only_sees_production_finishing_material_and_customer_menus(): void
    {
        $this->assertMenuFor(
            UserRole::Production,
            ['dashboard', 'customer', 'production', 'finishing', 'material'],
            ['order', 'design', 'delivery', 'finance', 'hrd', 'reports', 'divisions'],
        );
    }

    #[Test]
    public function field_only_sees_delivery_menu(): void
    {
        $this->assertMenuFor(
            UserRole::Field,
            ['dashboard', 'delivery'],
            ['customer', 'order', 'design', 'production', 'finance', 'divisions'],
        );
    }

    #[Test]
    public function finance_only_sees_finance_and_customer_menus(): void
    {
        $this->assertMenuFor(
            UserRole::Finance,
            ['dashboard', 'customer', 'finance'],
            ['order', 'design', 'production', 'hrd', 'delivery', 'divisions'],
        );
    }

    #[Test]
    public function hrd_only_sees_hrd_and_work_calendar_menu(): void
    {
        $this->assertMenuFor(
            UserRole::Hrd,
            ['dashboard', 'hrd', 'work-calendars'],
            ['customer', 'order', 'design', 'production', 'finance', 'delivery', 'divisions', 'users'],
        );
    }
}
