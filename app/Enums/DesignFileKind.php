<?php

namespace App\Enums;

/**
 * Jenis file desain (MOD-03, AC-03-3).
 *
 * Nilai mengikuti persetujuan Owner: draft|final.
 */
enum DesignFileKind: string
{
    case Draft = 'draft';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Final => 'Final',
        };
    }
}
