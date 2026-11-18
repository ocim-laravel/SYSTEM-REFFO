<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use App\Services\System\DemoAccountService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Akun demo untuk tiap role awal (AC-10-2 / gate PH-00).
 *
 * Ini adalah akun demo pengembangan: password diset ulang setiap seeder
 * dijalankan agar gate PH-00 mudah diuji. Jangan dipakai di production.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun demo (reffo12345 x 7) HANYA untuk pengembangan & gate PH-00.
        // Jangan pernah di-seed di production.
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('UserSeeder: dilewati (bukan environment local/testing).');

            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (DemoAccountService::accounts() as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);

            $user->name = $data['name'];
            $user->division_id = $data['division'] === null
                ? null
                : Division::where('code', $data['division'])->value('id');
            $user->is_active = true;
            $user->must_change_password = false;
            $user->password = DemoAccountService::PASSWORD;

            $user->save();

            if (Role::where('name', $data['role'])->exists()) {
                $user->syncRoles([$data['role']]);
            }
        }
    }
}
