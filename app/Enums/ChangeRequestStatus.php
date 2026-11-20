<?php

namespace App\Enums;

/**
 * Status Change Request (MOD-03, AC-03-6, BR-05).
 *
 * Nilai: pending|approved|rejected (disetujui Owner).
 */
enum ChangeRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
