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
        'divisions.view',
        'divisions.manage',
        'users.view',
        'users.manage',
    ];

    /**
     * Pemetaan role ke permission (mengacu struktur menu 04-architecture §4.6).
     *
     * @var array<string, list<string>>
     */
    public const ROLE_PERMISSIONS = [
        'owner' => self::PERMISSIONS,
        'admin' => [
            'menu.dashboard',
            'menu.customer',
            'menu.order',
        ],
        'designer' => [
            'menu.dashboard',
            'menu.order',
            'menu.design',
        ],
        'production' => [
            'menu.dashboard',
            'menu.production',
            'menu.finishing',
            'menu.material',
        ],
        'field' => [
            'menu.dashboard',
            'menu.delivery',
        ],
        'finance' => [
            'menu.dashboard',
            'menu.finance',
        ],
        'hrd' => [
            'menu.dashboard',
            'menu.hrd',
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
