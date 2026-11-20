<?php

namespace App\Enums;

/**
 * Kelas antrean produksi/cetak (MOD-04, BR-06, BR-07).
 */
enum PrintQueueClass: string
{
    case Urgent = 'urgent';
    case H1 = 'h1';
    case Regular = 'regular';

    public function label(): string
    {
        return match ($this) {
            self::Urgent => 'Urgent',
            self::H1 => 'H-1',
            self::Regular => 'Reguler',
        };
    }
}
