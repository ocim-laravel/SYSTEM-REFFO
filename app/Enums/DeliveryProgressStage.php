<?php

namespace App\Enums;

/**
 * Tahap progres petugas lapangan (MOD-06 fitur 5).
 * Penyelesaian dicatat terpisah lewat DeliveryService::complete().
 */
enum DeliveryProgressStage: string
{
    case Departed = 'departed';
    case Arrived = 'arrived';

    public function label(): string
    {
        return match ($this) {
            self::Departed => 'Berangkat',
            self::Arrived => 'Tiba di Lokasi',
        };
    }
}
