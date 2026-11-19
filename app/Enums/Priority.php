<?php

namespace App\Enums;

/**
 * Prioritas order/item (MOD-02, PH-02).
 *
 * Nilai mengikuti 05-data-model.md bagian "Status (Enum)" persis.
 */
enum Priority: string
{
    case Normal = 'normal';
    case H1 = 'h1';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::H1 => 'H-1',
            self::Urgent => 'Urgent',
        };
    }
}
