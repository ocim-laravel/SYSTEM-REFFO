<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Cleanup PH-00: peran tunggal kini hanya disimpan oleh spatie/laravel-permission.
 * Kolom `users.role` (legacy) dihapus setelah datanya dipindahkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Backfill: salin peran lama ke spatie hanya bila belum ada.
        foreach (User::query()->whereNotNull('role')->get() as $user) {
            $role = Role::query()
                ->where('name', $user->role)
                ->where('guard_name', 'web')
                ->first();

            if ($role !== null && ! $user->hasRole($role->name)) {
                $user->syncRoles([$role->name]);
            }
        }

        // Akun operator lama tidak dipakai lagi: dinonaktifkan, bukan dihapus.
        User::query()
            ->where('email', 'operator@reffo.test')
            ->update(['is_active' => false]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('operator')->after('email');
        });

        foreach (User::query()->get() as $user) {
            $user->forceFill(['role' => $user->getRoleNames()->first()])->save();
        }
    }
};
