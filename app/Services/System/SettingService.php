<?php

namespace App\Services\System;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Pengaturan sistem: nama perusahaan, logo, dan jam kerja (MOD-10, PH-00, BR-17).
 */
class SettingService
{
    /** @var array<string, string|null> */
    public const DEFAULTS = [
        'company_name' => 'REFFO Media',
        'company_logo' => null,
        'work_hours_start' => '08:00',
        'work_hours_end' => '17:00',
    ];

    public const LOGO_DISK = 'public';

    public const LOGO_DIRECTORY = 'settings';

    public function __construct(private readonly AuditLogService $auditLogs) {}

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        // Layout juga dirender sebelum migrasi dijalankan (mis. halaman install).
        if (! Schema::hasTable('settings')) {
            return self::DEFAULTS;
        }

        /** @var array<string, string|null> $stored */
        $stored = Setting::query()->pluck('value', 'key')->all();

        return array_merge(self::DEFAULTS, $stored);
    }

    public function value(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Simpan seluruh pengaturan dalam satu transaksi dan catat audit log (BR-17).
     *
     * @param  array{company_name: string, work_hours_start: string, work_hours_end: string, remove_logo?: bool}  $data
     * @return array<string, string|null>
     */
    public function update(
        array $data,
        ?UploadedFile $logo,
        ?User $actor = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): array {
        return DB::transaction(function () use ($data, $logo, $actor, $ip, $userAgent): array {
            $before = $this->all();

            $this->storeValue('company_name', $data['company_name'], 'text');
            $this->storeValue('work_hours_start', $data['work_hours_start'], 'time');
            $this->storeValue('work_hours_end', $data['work_hours_end'], 'time');

            if ($logo !== null) {
                $this->storeLogo($logo, $before['company_logo']);
            } elseif (! empty($data['remove_logo'])) {
                $this->deleteLogo($before['company_logo']);
                $this->storeValue('company_logo', null, 'file');
            }

            $after = $this->all();

            $this->auditLogs->record(
                action: 'settings.updated',
                actor: $actor,
                modelType: Setting::class,
                before: $before,
                after: $after,
                ip: $ip,
                userAgent: $userAgent,
            );

            return $after;
        });
    }

    private function storeValue(string $key, ?string $value, string $type): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => 'general'],
        );
    }

    private function storeLogo(UploadedFile $logo, ?string $oldPath): void
    {
        $this->deleteLogo($oldPath);

        $this->storeValue(
            'company_logo',
            $logo->store(self::LOGO_DIRECTORY, self::LOGO_DISK),
            'file',
        );
    }

    private function deleteLogo(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk(self::LOGO_DISK)->delete($path);
        }
    }
}
