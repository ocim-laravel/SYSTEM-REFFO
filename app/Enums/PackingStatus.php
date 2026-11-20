<?php

namespace App\Enums;

/**
 * Status pengepakan item (MOD-05).
 */
enum PackingStatus: string
{
    case InProgress = 'in_progress';
    case Packed = 'packed';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Berjalan',
            self::Packed => 'Dikemas',
        };
    }
}
