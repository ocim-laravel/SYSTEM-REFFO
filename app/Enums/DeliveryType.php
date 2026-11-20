<?php

namespace App\Enums;

/**
 * Tiga tipe pekerjaan lapangan (MOD-06, 05-data-model.md).
 */
enum DeliveryType: string
{
    case Delivery = 'delivery';
    case Installation = 'installation';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Delivery => 'Pengiriman',
            self::Installation => 'Pemasangan',
            self::Pickup => 'Ambil Sendiri',
        };
    }
}
