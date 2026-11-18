<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Services\System\WorkCalendarService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->forRole(UserRole::Owner)->create();
    }

    #[Test]
    public function it_returns_friday_as_previous_workday_for_monday_deadline(): void
    {
        $service = app(WorkCalendarService::class);

        $previous = $service->previousWorkday('2026-11-23'); // Senin

        $this->assertSame('2026-11-20', $previous->toDateString()); // Jumat
    }

    #[Test]
    public function it_skips_holidays_when_calculating_previous_workday(): void
    {
        WorkCalendar::create([
            'date' => '2026-11-20', // Jumat ditandai libur
            'is_workday' => false,
            'note' => 'Libur perusahaan',
        ]);

        $service = app(WorkCalendarService::class);

        $previous = $service->previousWorkday('2026-11-23'); // Senin

        $this->assertSame('2026-11-19', $previous->toDateString()); // Kamis
    }

    #[Test]
    public function it_skips_weekend_and_holidays_when_calculating_previous_workday(): void
    {
        WorkCalendar::create([
            'date' => '2026-11-20', // Jumat libur
            'is_workday' => false,
            'note' => 'Libur',
        ]);
        WorkCalendar::create([
            'date' => '2026-11-19', // Kamis libur
            'is_workday' => false,
            'note' => 'Libur',
        ]);

        $service = app(WorkCalendarService::class);

        $previous = $service->previousWorkday('2026-11-23'); // Senin

        $this->assertSame('2026-11-18', $previous->toDateString()); // Rabu
    }

    #[Test]
    public function it_allows_owner_to_create_a_work_calendar_entry(): void
    {
        $this->actingAs($this->owner)
            ->post(route('system.work-calendars.store'), [
                'date' => '2026-12-25',
                'is_workday' => '0',
                'note' => 'Hari libur nasional',
            ])
            ->assertRedirect(route('system.work-calendars.index'));

        $this->assertDatabaseHas('work_calendars', [
            'date' => '2026-12-25',
            'is_workday' => 0,
            'note' => 'Hari libur nasional',
        ]);
    }

    #[Test]
    public function it_allows_hrd_to_manage_work_calendars(): void
    {
        $hrd = User::factory()->forRole(UserRole::Hrd)->create();
        $entry = WorkCalendar::create([
            'date' => '2026-12-24',
            'is_workday' => true,
            'note' => 'Masuk setengah hari',
        ]);

        $this->actingAs($hrd)
            ->get(route('system.work-calendars.index'))
            ->assertOk()
            ->assertSee('2026-12-24');

        $this->actingAs($hrd)
            ->put(route('system.work-calendars.update', $entry), [
                'date' => '2026-12-24',
                'is_workday' => '0',
                'note' => 'Cuti bersama',
            ])
            ->assertRedirect(route('system.work-calendars.index'));

        $this->assertDatabaseHas('work_calendars', [
            'id' => $entry->id,
            'is_workday' => 0,
            'note' => 'Cuti bersama',
        ]);
    }

    #[Test]
    public function it_updates_and_deletes_a_work_calendar_entry(): void
    {
        $entry = WorkCalendar::create([
            'date' => '2026-12-31',
            'is_workday' => true,
            'note' => null,
        ]);

        $this->actingAs($this->owner)
            ->put(route('system.work-calendars.update', $entry), [
                'date' => '2026-12-31',
                'is_workday' => '0',
                'note' => 'Tutup akhir tahun',
            ])
            ->assertRedirect(route('system.work-calendars.index'));

        $this->assertDatabaseHas('work_calendars', [
            'id' => $entry->id,
            'is_workday' => 0,
            'note' => 'Tutup akhir tahun',
        ]);

        $this->actingAs($this->owner)
            ->delete(route('system.work-calendars.destroy', $entry))
            ->assertRedirect(route('system.work-calendars.index'));

        $this->assertDatabaseMissing('work_calendars', ['id' => $entry->id]);
    }

    #[Test]
    public function it_rejects_a_duplicate_work_calendar_date(): void
    {
        WorkCalendar::create([
            'date' => '2026-08-17',
            'is_workday' => false,
            'note' => 'Hari Kemerdekaan',
        ]);

        $this->actingAs($this->owner)
            ->post(route('system.work-calendars.store'), [
                'date' => '2026-08-17',
                'is_workday' => '0',
                'note' => 'Duplikat',
            ])
            ->assertSessionHasErrors('date');

        $this->assertSame(1, WorkCalendar::query()->count());
    }

    #[Test]
    public function it_forbids_roles_without_permission_from_managing_work_calendars(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('system.work-calendars.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('system.work-calendars.store'), [
                'date' => '2026-12-25',
                'is_workday' => '0',
                'note' => 'Coba',
            ])
            ->assertForbidden();
    }
}
