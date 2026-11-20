<?php

namespace App\Enums;

/**
 * Status pekerjaan desain (MOD-03, AC-03-2).
 *
 * Nilai mengikuti persetujuan Owner: assigned|in_progress|submitted|approved.
 */
enum DesignJobStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Ditugaskan',
            self::InProgress => 'Sedang Dikerjakan',
            self::Submitted => 'Diserahkan',
            self::Approved => 'Disetujui',
        };
    }
}
