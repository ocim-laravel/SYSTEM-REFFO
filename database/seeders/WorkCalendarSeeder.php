<?php

namespace Database\Seeders;

use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Kalender kerja default: akhir pekan + hari libur nasional (MOD-10, BR-07).
 *
 * Sumber tanggal: SKB 3 Menteri tentang Hari Libur Nasional dan Cuti Bersama
 * tahun 2026. Entri dibuat idempoten dan bisa ditimpa lewat CRUD Owner/HRD.
 */
class WorkCalendarSeeder extends Seeder
{
    /** Tahun yang memiliki daftar libur nasional ter-seed. */
    public const YEAR = 2026;

    /** @var array<string, string> */
    public const NATIONAL_HOLIDAYS = [
        '2026-01-01' => 'Tahun Baru 2026 Masehi',
        '2026-01-16' => 'Isra Mikraj Nabi Muhammad SAW',
        '2026-02-17' => 'Tahun Baru Imlek 2577 Kongzili',
        '2026-03-19' => 'Hari Suci Nyepi Tahun Baru Saka 1948',
        '2026-03-21' => 'Hari Raya Idul Fitri 1447 Hijriah',
        '2026-03-22' => 'Hari Raya Idul Fitri 1447 Hijriah',
        '2026-04-03' => 'Wafat Yesus Kristus',
        '2026-04-05' => 'Kebangkitan Yesus Kristus (Paskah)',
        '2026-05-01' => 'Hari Buruh Internasional',
        '2026-05-14' => 'Kenaikan Yesus Kristus',
        '2026-05-27' => 'Hari Raya Idul Adha 1447 Hijriah',
        '2026-05-31' => 'Hari Raya Waisak 2570 BE',
        '2026-06-01' => 'Hari Lahir Pancasila',
        '2026-06-16' => 'Tahun Baru Islam 1448 Hijriah',
        '2026-08-17' => 'Proklamasi Kemerdekaan Republik Indonesia',
        '2026-08-25' => 'Maulid Nabi Muhammad SAW',
        '2026-12-25' => 'Hari Raya Natal',
    ];

    /** @var array<string, string> */
    public const SHARED_LEAVE = [
        '2026-02-16' => 'Cuti Bersama Tahun Baru Imlek 2577 Kongzili',
        '2026-03-18' => 'Cuti Bersama Hari Suci Nyepi',
        '2026-03-20' => 'Cuti Bersama Hari Raya Idul Fitri 1447 Hijriah',
        '2026-03-23' => 'Cuti Bersama Hari Raya Idul Fitri 1447 Hijriah',
        '2026-03-24' => 'Cuti Bersama Hari Raya Idul Fitri 1447 Hijriah',
        '2026-05-15' => 'Cuti Bersama Kenaikan Yesus Kristus',
        '2026-05-28' => 'Cuti Bersama Hari Raya Idul Adha 1447 Hijriah',
        '2026-12-24' => 'Cuti Bersama Hari Raya Natal',
    ];

    public function run(?int $year = null): void
    {
        $year ??= now()->year;
        $rows = $this->weekendRows($year);

        foreach ($this->holidaysFor($year) as $date => $note) {
            $rows[$date] = [
                'date' => $date,
                'is_workday' => false,
                'note' => $note,
            ];
        }

        ksort($rows);
        $timestamp = now()->toDateTimeString();
        $records = array_map(
            static fn (array $row): array => $row + ['created_at' => $timestamp, 'updated_at' => $timestamp],
            array_values($rows),
        );

        foreach (array_chunk($records, 100) as $chunk) {
            WorkCalendar::query()->upsert(
                $chunk,
                ['date'],
                ['is_workday', 'note', 'updated_at'],
            );
        }
    }

    /**
     * @return array<string, array{date: string, is_workday: bool, note: string}>
     */
    private function weekendRows(int $year): array
    {
        $rows = [];
        $cursor = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $last = $cursor->copy()->endOfYear();

        while ($cursor->lte($last)) {
            if ($cursor->isWeekend()) {
                $date = $cursor->toDateString();
                $rows[$date] = [
                    'date' => $date,
                    'is_workday' => false,
                    'note' => 'Akhir pekan',
                ];
            }

            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function holidaysFor(int $year): array
    {
        if ($year !== self::YEAR) {
            return [];
        }

        return array_merge(self::NATIONAL_HOLIDAYS, self::SHARED_LEAVE);
    }
}
