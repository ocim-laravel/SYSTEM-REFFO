<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Menyemai 7 role awal (AC-10-2) beserta permission menu dan aksi.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Permission yang dipakai menu sidebar dan gerbang aksi.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'menu.dashboard',
        'menu.customer',
        'menu.order',
        'menu.design',
        'menu.production',
        'menu.finishing',
        'menu.delivery',
        'menu.finance',
        'menu.hrd',
        'menu.material',
        'menu.reports',
        'menu.system',
        'customers.view',
        'customers.manage',
        'customers.delete',
        'orders.view',
        'orders.validate',
        'orders.manage',
        'divisions.view',
        'divisions.manage',
        'users.view',
        'users.manage',
        'calendars.view',
        'calendars.manage',
        'system.override',
        'settings.view',
        'settings.manage',
    ];

    /**
     * Pemetaan role ke permission (mengacu struktur menu 04-architecture, MOD-02).
     *
     * @var array<string, list<string>>
     */
    public const ROLE_PERMISSIONS = [
        'owner' => [
            'menu.dashboard',
            'menu.customer',
            'menu.order',
            'menu.design',
            'menu.production',
            'menu.finishing',
            'menu.delivery',
            'menu.finance',
            'menu.hrd',
            'menu.material',
            'menu.reports',
            'menu.system',
            'customers.view',
            'customers.manage',
            'customers.delete',
            'orders.view',
            'orders.manage',
            'divisions.view',
            'divisions.manage',
            'users.view',
            'users.manage',
            'calendars.view',
            'calendars.manage',
            'system.override',
            'settings.view',
            'settings.manage',
        ],
        'admin' => [
            'menu.dashboard',
            'menu.customer',
            'menu.order',
            'customers.view',
            'customers.manage',
            'orders.view',
            'orders.validate',
            'orders.manage',
        ],
        'designer' => [
            'menu.dashboard',
            'menu.order',
            'menu.design',
            'orders.view',
        ],
        'production' => [
            'menu.dashboard',
            'menu.customer',
            'menu.production',
            'menu.finishing',
            'menu.material',
            'customers.view',
        ],
        'field' => [
            'menu.dashboard',
            'menu.delivery',
        ],
        'finance' => [
            'menu.dashboard',
            'menu.customer',
            'menu.finance',
            'customers.view',
        ],
        'hrd' => [
            'menu.dashboard',
            'menu.hrd',
            'calendars.view',
            'calendars.manage',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (UserRole::values() as $role) {
            $model = Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);

            $model->syncPermissions(self::ROLE_PERMISSIONS[$role] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
