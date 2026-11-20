<?php

namespace App\Enums;

/**
 * Status pekerjaan cetak/print job (MOD-04).
 */
enum PrintJobStatus: string
{
    case Queued = 'queued';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Antrean',
            self::InProgress => 'Sedang Dikerjakan',
            self::Completed => 'Selesai',
        };
    }
}
