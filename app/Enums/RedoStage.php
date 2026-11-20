<?php

namespace App\Enums;

/**
 * Tahap yang diulang saat rework (MOD-05 "Aturan penerapan").
 *
 * Kembali ke cetak wajib melalui alur cetak ulang (BR-11).
 */
enum RedoStage: string
{
    case Cetak = 'cetak';
    case Finishing = 'finishing';

    public function label(): string
    {
        return match ($this) {
            self::Cetak => 'Cetak',
            self::Finishing => 'Finishing',
        };
    }
}
