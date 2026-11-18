<?php

namespace Tests\Feature\System;

use App\Models\WorkCalendar;
use App\Services\System\WorkCalendarService;
use Database\Seeders\WorkCalendarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkCalendarSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_weekend_as_non_workday(): void
    {
        $this->seed(WorkCalendarSeeder::class);

        $this->assertDatabaseHas('work_calendars', [
            'date' => '2026-11-21', // Sabtu
            'is_workday' => 0,
            'note' => 'Akhir pekan',
        ]);
        $this->assertDatabaseHas('work_calendars', [
            'date' => '2026-11-22', // Minggu
            'is_workday' => 0,
        ]);
    }

    #[Test]
    public function it_seeds_national_holidays_as_non_workday(): void
    {
        $this->seed(WorkCalendarSeeder::class);

        $this->assertDatabaseHas('work_calendars', [
            'date' => '2026-08-17',
            'is_workday' => 0,
            'note' => 'Proklamasi Kemerdekaan Republik Indonesia',
        ]);
        $this->assertDatabaseHas('work_calendars', [
            'date' => '2026-12-25',
            'is_workday' => 0,
            'note' => 'Hari Raya Natal',
        ]);
    }

    #[Test]
    public function it_uses_holiday_note_when_holiday_falls_on_weekend(): void
    {
        $this->seed(WorkCalendarSeeder::class);

        $entry = WorkCalendar::query()->whereDate('date', '2026-03-21')->firstOrFail();

        $this->assertFalse($entry->is_workday);
        $this->assertSame('Hari Raya Idul Fitri 1447 Hijriah', $entry->note);
    }

    #[Test]
    public function it_does_not_mark_regular_weekday_as_holiday(): void
    {
        $this->seed(WorkCalendarSeeder::class);

        $this->assertDatabaseMissing('work_calendars', ['date' => '2026-11-19']); // Kamis
    }

    #[Test]
    public function it_is_idempotent_when_seeded_twice(): void
    {
        $this->seed(WorkCalendarSeeder::class);
        $count = WorkCalendar::query()->count();

        $this->seed(WorkCalendarSeeder::class);

        $this->assertSame($count, WorkCalendar::query()->count());
    }

    #[Test]
    public function it_makes_previous_workday_skip_seeded_weekend(): void
    {
        $this->seed(WorkCalendarSeeder::class);

        $previous = app(WorkCalendarService::class)->previousWorkday('2026-11-23'); // Senin

        $this->assertSame('2026-11-20', $previous->toDateString()); // Jumat
    }

    #[Test]
    public function it_makes_previous_workday_skip_seeded_national_holiday(): void
    {
        $this->seed(WorkCalendarSeeder::class);

        $previous = app(WorkCalendarService::class)->previousWorkday('2026-08-18'); // Selasa

        $this->assertSame('2026-08-14', $previous->toDateString()); // Jumat (17 Agustus libur)
    }
}
