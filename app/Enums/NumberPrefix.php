<?php

namespace App\Enums;

/**
 * Awalan nomor otomatis fondasi sistem (MOD-10, PH-00).
 *
 * Dipakai bersama modul lain lewat NumberGeneratorService; format akhir:
 * PREFIX-TAHUN-0001.
 */
enum NumberPrefix: string
{
    case Customer = 'CUS';
    case Order = 'ORD';
    case Item = 'ITM';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Pelanggan',
            self::Order => 'Order',
            self::Item => 'Item',
        };
    }
}
