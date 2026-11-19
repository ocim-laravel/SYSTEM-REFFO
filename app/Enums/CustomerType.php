<?php

namespace App\Enums;

/**
 * Tipe pelanggan (MOD-01, PH-01).
 */
enum CustomerType: string
{
    case Company = 'perusahaan';
    case Individual = 'individu';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Perusahaan',
            self::Individual => 'Individu',
        };
    }
}
