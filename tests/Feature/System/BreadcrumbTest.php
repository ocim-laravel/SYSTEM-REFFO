<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BreadcrumbTest extends TestCase
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
    public function it_shows_only_beranda_as_current_crumb_on_dashboard(): void
    {
        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('aria-current="page">Beranda</span>', false);
    }

    #[Test]
    public function it_shows_sistem_and_pengaturan_crumbs_on_settings_page(): void
    {
        $this->actingAs($this->owner)
            ->get(route('system.settings.index'))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('Beranda', false)
            ->assertSee('Sistem', false)
            ->assertSee('aria-current="page">Pengaturan</span>', false);
    }

    #[Test]
    public function it_uses_indonesian_label_for_work_calendar_crumb(): void
    {
        $this->actingAs($this->owner)
            ->get(route('system.work-calendars.index'))
            ->assertOk()
            ->assertSee('Kalender Kerja', false);
    }

    #[Test]
    public function it_marks_exactly_one_crumb_as_current(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('system.settings.index'))
            ->assertOk();

        $content = $response->getContent();
        $this->assertSame(1, substr_count((string) $content, 'aria-current="page"'));
    }
}
