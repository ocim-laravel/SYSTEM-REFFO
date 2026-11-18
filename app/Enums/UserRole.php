<?php

namespace App\Enums;

/**
 * Tujuh role awal sistem (MOD-10, PH-00).
 *
 * Nilai enum ini adalah sumber kebenaran untuk nama role yang disimpan
 * oleh spatie/laravel-permission dan dipakai oleh Policy, Gate, seeder,
 * serta menu sidebar.
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Designer = 'designer';
    case Production = 'production';
    case Field = 'field';
    case Finance = 'finance';
    case Hrd = 'hrd';

    /**
     * Label bahasa Indonesia untuk ditampilkan di UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Designer => 'Designer',
            self::Production => 'Produksi',
            self::Field => 'Lapangan',
            self::Finance => 'Keuangan',
            self::Hrd => 'HRD',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
