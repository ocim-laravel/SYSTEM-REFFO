<?php

namespace App\Enums;

/**
 * Tindakan reklame pada item (MOD-02, PH-02 — sesuai revisi UI ADR-007 §4/§5).
 *
 * `konstruksi/rangka` dan `pemasangan` disimpan sebagai catatan pada
 * `order_items.specification.tindakan` pada fase ini; scheduling pemasangan
 * nyata tetap berada di luar lingkup (MOD-06, BR-13).
 */
enum ActionType: string
{
    case KonstruksiRangka = 'konstruksi_rangka';
    case Pemasangan = 'pemasangan';

    public function label(): string
    {
        return match ($this) {
            self::KonstruksiRangka => 'Konstruksi/Rangka',
            self::Pemasangan => 'Pemasangan',
        };
    }
}
