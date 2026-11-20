<?php

namespace App\Enums;

/**
 * Status pekerjaan finishing per item (MOD-05).
 */
enum FinishingJobStatus: string
{
    case Queued = 'queued';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Menunggu',
            self::InProgress => 'Berjalan',
            self::Completed => 'Selesai',
        };
    }
}
