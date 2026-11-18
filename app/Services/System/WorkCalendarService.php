<?php

namespace App\Services\System;

use App\Models\WorkCalendar;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Business logic kalender kerja (MOD-10, AC-10-5, BR-07).
 */
class WorkCalendarService
{
    /**
     * @param  array{date: string, is_workday?: bool, note?: string|null}  $data
     */
    public function create(array $data): WorkCalendar
    {
        return DB::transaction(fn (): WorkCalendar => WorkCalendar::create($this->payload($data)));
    }

    /**
     * @param  array{date: string, is_workday?: bool, note?: string|null}  $data
     */
    public function update(WorkCalendar $workCalendar, array $data): WorkCalendar
    {
        DB::transaction(fn () => $workCalendar->update($this->payload($data)));

        return $workCalendar->refresh();
    }

    public function delete(WorkCalendar $workCalendar): void
    {
        DB::transaction(fn () => $workCalendar->delete());
    }

    /**
     * Hitung satu hari kerja sebelum tanggal acuan (BR-07).
     *
     * Sumber kebenaran: work_calendars. Jika tanggal tidak tercatat,
     * Senin–Jumat dianggap hari kerja dan Sabtu–Minggu bukan.
     */
    public function previousWorkday(DateTimeInterface|string $date): Carbon
    {
        $cursor = Carbon::parse($date, config('app.timezone'))->startOfDay()->subDay();

        while (! $this->isWorkday($cursor)) {
            $cursor->subDay();
        }

        return $cursor;
    }

    public function isWorkday(Carbon $date): bool
    {
        $entry = WorkCalendar::query()->whereDate('date', $date->toDateString())->first();

        if ($entry !== null) {
            return $entry->is_workday;
        }

        return ! $date->isWeekend();
    }

    /**
     * @param  array{date: string, is_workday?: bool, note?: string|null}  $data
     * @return array{date: string, is_workday: bool, note: string|null}
     */
    private function payload(array $data): array
    {
        return [
            'date' => Carbon::parse($data['date'])->toDateString(),
            'is_workday' => (bool) ($data['is_workday'] ?? true),
            'note' => $data['note'] ?? null,
        ];
    }
}
