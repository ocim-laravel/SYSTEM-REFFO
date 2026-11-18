<?php

namespace App\Enums;

/**
 * Aturan fondasi (MOD-10) yang dapat di-override oleh Owner (BR-18).
 *
 * Nilai ini dipakai sebagai bagian dari nama aksi audit log
 * (`override.{value}`) agar jejak bypass aturan dapat ditelusuri.
 */
enum OverridableRule: string
{
    case ActiveUser = 'active_user';
    case MandatoryPasswordChange = 'mandatory_password_change';
    case AccessPermission = 'access_permission';
    case WorkCalendar = 'work_calendar';

    public function label(): string
    {
        return match ($this) {
            self::ActiveUser => 'User nonaktif ditolak',
            self::MandatoryPasswordChange => 'Kewajiban ganti password',
            self::AccessPermission => 'Pemeriksaan hak akses',
            self::WorkCalendar => 'Kalender kerja & hari libur',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $rule): string => $rule->value, self::cases());
    }
}
