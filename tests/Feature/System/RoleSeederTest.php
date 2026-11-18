<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_the_seven_initial_roles_when_seeder_runs(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(7, Role::query()->count());

        foreach (UserRole::values() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function owner_role_receives_every_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = Role::findByName('owner', 'web');

        $this->assertEqualsCanonicalizing(
            RolePermissionSeeder::PERMISSIONS,
            $owner->permissions->pluck('name')->all(),
        );
    }

    #[Test]
    public function each_role_only_receives_its_own_menu_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (RolePermissionSeeder::ROLE_PERMISSIONS as $role => $expected) {
            $actual = Role::findByName($role, 'web')->permissions->pluck('name')->all();

            $this->assertEqualsCanonicalizing($expected, $actual, "Permission role {$role} tidak sesuai.");
        }
    }

    #[Test]
    public function finance_role_cannot_see_production_or_customer_menu_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $finance = Role::findByName('finance', 'web');

        $this->assertTrue($finance->hasPermissionTo('menu.finance'));
        $this->assertFalse($finance->hasPermissionTo('menu.production'));
        $this->assertFalse($finance->hasPermissionTo('menu.customer'));
        $this->assertFalse($finance->hasPermissionTo('divisions.manage'));
    }
}
