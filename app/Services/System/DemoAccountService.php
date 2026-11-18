<?php

namespace App\Services\System;

/**
 * Sumber tunggal akun demo pengembangan.
 *
 * Dipakai oleh UserSeeder (membuat akun) dan halaman login (tabel bantuan
 * agar email/password mudah disalin). Tabel hanya dirender pada environment
 * non-production melalui isEnabled().
 */
class DemoAccountService
{
    public const PASSWORD = 'reffo12345';

    /**
     * @return array<int, array{role: string, name: string, email: string, division: string|null}>
     */
    public static function accounts(): array
    {
        return [
            ['role' => 'owner', 'name' => 'Owner REFFO', 'email' => 'owner@reffo.test', 'division' => null],
            ['role' => 'admin', 'name' => 'Admin REFFO', 'email' => 'admin@reffo.test', 'division' => 'ADM'],
            ['role' => 'designer', 'name' => 'Designer REFFO', 'email' => 'designer@reffo.test', 'division' => 'DSN'],
            ['role' => 'production', 'name' => 'Produksi REFFO', 'email' => 'production@reffo.test', 'division' => 'PRD'],
            ['role' => 'field', 'name' => 'Lapangan REFFO', 'email' => 'field@reffo.test', 'division' => 'LPN'],
            ['role' => 'finance', 'name' => 'Keuangan REFFO', 'email' => 'finance@reffo.test', 'division' => 'KEU'],
            ['role' => 'hrd', 'name' => 'HRD REFFO', 'email' => 'hrd@reffo.test', 'division' => 'HRD'],
        ];
    }

    public static function isEnabled(): bool
    {
        return app()->environment(['local', 'development', 'testing']);
    }
}
