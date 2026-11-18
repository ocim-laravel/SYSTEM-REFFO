<?php

namespace App\Services\System;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Business logic CRUD pengguna (MOD-10, AC-10-4).
 *
 * Setiap perubahan penting dicatat ke audit log dengan snapshot
 * before/after tanpa-data sensitif (BR-17).
 */
class UserService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array{name: string, email: string, password: string, role: string, division_id?: int|null}  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function create(array $data, User $actor, array $meta = []): User
    {
        return DB::transaction(function () use ($data, $actor, $meta): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'division_id' => $data['division_id'] ?? null,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $this->syncRole($user, $data['role']);

            $this->audit->record(
                action: 'user.created',
                actor: $actor,
                modelType: User::class,
                modelId: $user->id,
                after: $this->snapshot($user),
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string, role: string, division_id?: int|null, is_active?: bool}  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function update(User $target, array $data, User $actor, array $meta = []): User
    {
        $before = $this->snapshot($target);

        DB::transaction(function () use ($target, $data, $actor, $meta, $before): void {
            $target->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'division_id' => $data['division_id'] ?? null,
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : $target->is_active,
            ]);

            $this->syncRole($target, $data['role']);
            $target->refresh();

            $this->audit->record(
                action: 'user.updated',
                actor: $actor,
                modelType: User::class,
                modelId: $target->id,
                before: $before,
                after: $this->snapshot($target),
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );
        });

        return $target;
    }

    /**
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function delete(User $target, User $actor, array $meta = []): void
    {
        DB::transaction(function () use ($target, $actor, $meta): void {
            $before = $this->snapshot($target);
            $id = $target->id;

            $target->delete();

            $this->audit->record(
                action: 'user.deleted',
                actor: $actor,
                modelType: User::class,
                modelId: $id,
                before: $before,
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );
        });
    }

    /**
     * Aktifkan/nonaktifkan akun dan catat alasannya (BR-17).
     *
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function setActive(User $target, bool $isActive, User $actor, array $meta = []): User
    {
        $before = $this->snapshot($target);

        DB::transaction(function () use ($target, $isActive, $actor, $meta, $before): void {
            $target->update(['is_active' => $isActive]);
            $target->refresh();

            $this->audit->record(
                action: 'user.updated',
                actor: $actor,
                modelType: User::class,
                modelId: $target->id,
                before: $before,
                after: $this->snapshot($target),
                reason: $isActive ? 'Pengguna diaktifkan.' : 'Pengguna dinonaktifkan.',
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );
        });

        return $target;
    }

    /**
     * Reset password oleh administrator; pengguna wajib menggantinya saat login.
     *
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function resetPassword(User $target, string $password, User $actor, array $meta = []): User
    {
        $before = $this->snapshot($target);

        DB::transaction(function () use ($target, $password, $actor, $meta, $before): void {
            $target->update([
                'password' => $password,
                'must_change_password' => true,
            ]);
            $target->refresh();

            $this->audit->record(
                action: 'user.password_reset',
                actor: $actor,
                modelType: User::class,
                modelId: $target->id,
                before: $before,
                after: $this->snapshot($target),
                reason: 'Password direset oleh administrator.',
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );
        });

        return $target;
    }

    private function syncRole(User $user, string $role): void
    {
        if (Role::query()->where('name', $role)->exists()) {
            $user->syncRoles([$role]);
        }
    }

    /**
     * Snapshot audit tanpa password/token sensitif.
     *
     * @return array<string, mixed>
     */
    private function snapshot(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roleEnum()?->value,
            'division_id' => $user->division_id,
            'is_active' => (bool) $user->is_active,
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }
}
