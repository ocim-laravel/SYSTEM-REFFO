<?php

namespace App\Services\System;

use App\Enums\UserRole;
use App\Events\RealtimeNotification;
use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Infrastruktur realtime MOD-10 (AC-10-6).
 *
 * Nama kanal di sini belum memakai awalan "private-": awalan ditambahkan oleh
 * PrivateChannel di server dan oleh Laravel Echo di browser. Otorisasi kanal
 * memeriksa role DAN divisi sesuai 04-architecture §4.5.
 */
class RealtimeService
{
    /** Batas payload per event sesuai 04-architecture §4.4 (4 KB). */
    public const MAX_PAYLOAD_BYTES = 4096;

    /**
     * Pemetaan role → kode divisi, mengikuti RolePermissionSeeder & DivisionSeeder.
     *
     * @var array<string, string>
     */
    private const ROLE_DIVISION = [
        UserRole::Admin->value => 'ADM',
        UserRole::Designer->value => 'DSN',
        UserRole::Production->value => 'PRD',
        UserRole::Field->value => 'LPN',
        UserRole::Finance->value => 'KEU',
        UserRole::Hrd->value => 'HRD',
    ];

    public function roleChannel(UserRole|string $role): string
    {
        return 'role.'.($role instanceof UserRole ? $role->value : $role);
    }

    public function divisionChannel(Division|string $division): string
    {
        $code = $division instanceof Division ? $division->code : $division;

        return 'division.'.strtolower($code);
    }

    public function userChannel(User|int $user): string
    {
        return 'user.'.($user instanceof User ? $user->id : $user);
    }

    public function canListenToRoleChannel(User $user, string $role): bool
    {
        if (UserRole::tryFrom($role) === null) {
            return false;
        }

        return $this->isOwner($user) || $user->hasRole($role);
    }

    public function canListenToDivisionChannel(User $user, string $code): bool
    {
        $code = strtoupper($code);

        if (! in_array($code, self::ROLE_DIVISION, true)) {
            return false;
        }

        if ($this->isOwner($user)) {
            return true;
        }

        $role = $user->roleEnum();

        if ($role === null) {
            return false;
        }

        return (self::ROLE_DIVISION[$role->value] ?? null) === $code
            && $user->division?->code === $code;
    }

    public function canListenToUserChannel(User $user, int|string $id): bool
    {
        return (string) $user->id === (string) $id;
    }

    /**
     * Kanal logical (tanpa awalan "private-") yang boleh didengar seorang user.
     *
     * @return list<string>
     */
    public function channelsFor(User $user): array
    {
        $channels = [$this->userChannel($user)];

        $role = $user->roleEnum();

        if ($role !== null) {
            $channels[] = $this->roleChannel($role);
        }

        if ($user->division !== null) {
            $channels[] = $this->divisionChannel($user->division);
        }

        return $channels;
    }

    /**
     * Kirim event uji di dalam transaksi. Karena event menandai
     * ShouldDispatchAfterCommit, pengiriman baru terjadi setelah commit.
     */
    public function sendTestNotification(User $user, string $message): RealtimeNotification
    {
        $this->guardPayloadSize($message);

        $event = new RealtimeNotification($message, null, $this->channelsFor($user));

        DB::transaction(fn () => event($event));

        return $event;
    }

    private function isOwner(User $user): bool
    {
        return $user->hasRole(UserRole::Owner->value);
    }

    private function guardPayloadSize(string $message): void
    {
        $payload = json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);

        if ($payload === false || strlen($payload) > self::MAX_PAYLOAD_BYTES) {
            throw new InvalidArgumentException('Payload event realtime melebihi batas 4 KB.');
        }
    }
}
