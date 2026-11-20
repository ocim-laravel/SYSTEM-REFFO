<?php

namespace App\Enums;

/**
 * Hasil pemeriksaan Quality Control (MOD-05, 05-data-model.md bagian F).
 */
enum QcResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Lulus',
            self::Fail => 'Gagal',
        };
    }
}
