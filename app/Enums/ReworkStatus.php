<?php

namespace App\Enums;

/**
 * Status pekerjaan ulang (MOD-05).
 */
enum ReworkStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::InProgress => 'Berjalan',
            self::Done => 'Selesai',
        };
    }
}
