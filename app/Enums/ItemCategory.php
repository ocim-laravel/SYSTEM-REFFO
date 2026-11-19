<?php

namespace App\Enums;

/**
 * Kategori item order (MOD-02, PH-02).
 *
 * Nilai mengikuti 05-data-model.md bagian C: printing / reklame.
 */
enum ItemCategory: string
{
    case Printing = 'printing';
    case Reklame = 'reklame';

    public function label(): string
    {
        return match ($this) {
            self::Printing => 'Printing',
            self::Reklame => 'Reklame',
        };
    }
}
