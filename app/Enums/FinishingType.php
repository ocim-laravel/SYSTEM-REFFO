<?php

namespace App\Enums;

/**
 * Jenis finishing item (MOD-02, PH-02 — sesuai revisi UI ADR-007 §4).
 *
 * Nilai sederhana, terdokumentasi, dan dipakai untuk
 * `order_items.specification.jenis_finishing`.
 */
enum FinishingType: string
{
    case Laminasi = 'laminasi';
    case Potong = 'potong';
    case Lipat = 'lipat';
    case Jahit = 'jahit';

    public function label(): string
    {
        return match ($this) {
            self::Laminasi => 'Laminasi',
            self::Potong => 'Potong',
            self::Lipat => 'Lipat',
            self::Jahit => 'Jahit',
        };
    }
}
