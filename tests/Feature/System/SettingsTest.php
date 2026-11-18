<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\System\SettingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->forRole(UserRole::Owner)->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'REFFO Media',
            'work_hours_start' => '08:00',
            'work_hours_end' => '17:00',
        ], $overrides);
    }

    #[Test]
    public function owner_sees_default_settings_when_nothing_is_stored(): void
    {
        $this->actingAs($this->owner)
            ->get(route('system.settings.index'))
            ->assertOk()
            ->assertSee('Pengaturan Sistem')
            ->assertSee('REFFO Media')
            ->assertSee('08:00')
            ->assertSee('17:00');

        $this->assertDatabaseCount('settings', 0);
    }

    #[Test]
    public function non_owner_cannot_view_settings_page(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('system.settings.index'))
            ->assertForbidden();
    }

    #[Test]
    public function owner_can_update_company_name_and_work_hours(): void
    {
        $this->actingAs($this->owner)
            ->put(route('system.settings.update'), $this->payload([
                'company_name' => 'REFFO Digital Printing',
                'work_hours_start' => '09:00',
                'work_hours_end' => '18:30',
            ]))
            ->assertRedirect(route('system.settings.index'))
            ->assertSessionHas('status', 'Pengaturan sistem berhasil disimpan.');

        $this->assertDatabaseHas('settings', ['key' => 'company_name', 'value' => 'REFFO Digital Printing']);
        $this->assertDatabaseHas('settings', ['key' => 'work_hours_start', 'value' => '09:00']);
        $this->assertDatabaseHas('settings', ['key' => 'work_hours_end', 'value' => '18:30']);

        $settings = app(SettingService::class)->all();
        $this->assertSame('REFFO Digital Printing', $settings['company_name']);
        $this->assertSame('09:00', $settings['work_hours_start']);
        $this->assertSame('18:30', $settings['work_hours_end']);
    }

    #[Test]
    public function it_rejects_update_when_company_name_is_blank(): void
    {
        $this->actingAs($this->owner)
            ->putJson(route('system.settings.update'), $this->payload(['company_name' => '   ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('company_name');

        $this->assertDatabaseCount('settings', 0);
    }

    #[Test]
    public function it_rejects_update_when_work_hours_end_is_not_after_start(): void
    {
        $this->actingAs($this->owner)
            ->putJson(route('system.settings.update'), $this->payload([
                'work_hours_start' => '17:00',
                'work_hours_end' => '08:00',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_hours_end');

        $this->assertDatabaseCount('settings', 0);
    }

    #[Test]
    public function it_rejects_update_when_work_hours_format_is_invalid(): void
    {
        $this->actingAs($this->owner)
            ->putJson(route('system.settings.update'), $this->payload([
                'work_hours_start' => '8 pagi',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_hours_start');
    }

    #[Test]
    public function non_owner_cannot_update_settings(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->put(route('system.settings.update'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('settings', 0);
    }

    #[Test]
    public function owner_can_upload_and_replace_company_logo(): void
    {
        Storage::fake(SettingService::LOGO_DISK);

        $this->actingAs($this->owner)
            ->put(route('system.settings.update'), $this->payload([
                'company_logo' => UploadedFile::fake()->create('logo-pertama.png', 10, 'image/png'),
            ]))
            ->assertRedirect(route('system.settings.index'));

        $firstPath = Setting::query()->where('key', 'company_logo')->value('value');
        $this->assertNotNull($firstPath);
        Storage::disk(SettingService::LOGO_DISK)->assertExists($firstPath);

        $this->actingAs($this->owner)
            ->put(route('system.settings.update'), $this->payload([
                'company_logo' => UploadedFile::fake()->create('logo-kedua.png', 10, 'image/png'),
            ]))
            ->assertRedirect(route('system.settings.index'));

        $secondPath = Setting::query()->where('key', 'company_logo')->value('value');
        $this->assertNotNull($secondPath);
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk(SettingService::LOGO_DISK)->assertExists($secondPath);
        Storage::disk(SettingService::LOGO_DISK)->assertMissing($firstPath);
    }

    #[Test]
    public function owner_can_remove_stored_company_logo(): void
    {
        Storage::fake(SettingService::LOGO_DISK);

        $this->actingAs($this->owner)
            ->put(route('system.settings.update'), $this->payload([
                'company_logo' => UploadedFile::fake()->create('logo.png', 10, 'image/png'),
            ]))
            ->assertRedirect(route('system.settings.index'));

        $path = Setting::query()->where('key', 'company_logo')->value('value');
        $this->assertNotNull($path);

        $this->actingAs($this->owner)
            ->put(route('system.settings.update'), $this->payload(['remove_logo' => true]))
            ->assertRedirect(route('system.settings.index'));

        Storage::disk(SettingService::LOGO_DISK)->assertMissing($path);
        $this->assertNull(Setting::query()->where('key', 'company_logo')->value('value'));
    }

    #[Test]
    public function it_rejects_logo_that_is_not_an_image(): void
    {
        Storage::fake(SettingService::LOGO_DISK);

        $this->actingAs($this->owner)
            ->putJson(route('system.settings.update'), $this->payload([
                'company_logo' => UploadedFile::fake()->create('logo.txt', 10, 'text/plain'),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('company_logo');
    }

    #[Test]
    public function it_records_before_and_after_in_audit_log_when_owner_updates_settings(): void
    {
        $this->actingAs($this->owner)
            ->put(route('system.settings.update'), $this->payload([
                'company_name' => 'REFFO Media Update',
            ]))
            ->assertRedirect(route('system.settings.index'));

        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('settings.updated', $log->action);
        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(Setting::class, $log->model_type);
        $this->assertSame('REFFO Media', $log->before['company_name']);
        $this->assertSame('REFFO Media Update', $log->after['company_name']);
    }

    #[Test]
    public function it_does_not_write_audit_log_when_validation_fails(): void
    {
        $this->actingAs($this->owner)
            ->putJson(route('system.settings.update'), $this->payload(['company_name' => '']))
            ->assertUnprocessable();

        $this->assertDatabaseCount('audit_logs', 0);
    }
}
