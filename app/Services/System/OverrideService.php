<?php

namespace App\Services\System;

use App\Enums\OverridableRule;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aksi override khusus Owner (MOD-10, BR-18).
 *
 * Override adalah bypass aturan yang sengaja dilakukan Owner. Setiap bypass
 * wajib memiliki alasan dan tercatat di audit log bersama snapshot
 * before/after serta identitas pelaku.
 */
class OverrideService
{
    private const SENSITIVE_KEYS = ['password', 'remember_token', 'token', 'api_token', 'secret'];

    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array{rule: OverridableRule|string, reason: string, target_type?: string|null, target_id?: int|null, before?: array<mixed>|null, after?: array<mixed>|null}  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function override(User $actor, array $data, array $meta = []): AuditLog
    {
        $reason = trim((string) ($data['reason'] ?? ''));

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan override wajib diisi.',
            ]);
        }

        $rule = $data['rule'] instanceof OverridableRule
            ? $data['rule']
            : OverridableRule::from((string) $data['rule']);

        return DB::transaction(fn (): AuditLog => $this->audit->record(
            action: 'override.'.$rule->value,
            actor: $actor,
            modelType: $data['target_type'] ?? null,
            modelId: $data['target_id'] ?? null,
            before: $this->sanitize($data['before'] ?? null),
            after: $this->sanitize($data['after'] ?? null),
            reason: $reason,
            ip: $meta['ip'] ?? null,
            userAgent: $meta['user_agent'] ?? null,
        ));
    }

    /**
     * Apakah Owner pernah meng-override aturan tertentu?
     *
     * Dipakai sebagai gerbang bypass oleh kode aturan lain: bypass hanya
     * dianggap sah bila ada catatan override yang sesuai (BR-18).
     */
    public function hasOverride(OverridableRule $rule, ?string $targetType = null, ?int $targetId = null): bool
    {
        return AuditLog::query()
            ->where('action', 'override.'.$rule->value)
            ->when($targetType !== null, fn ($query) => $query->where('model_type', $targetType))
            ->when($targetId !== null, fn ($query) => $query->where('model_id', $targetId))
            ->exists();
    }

    /**
     * Buang data sensitif dan nilai bukan skalar dari snapshot audit.
     *
     * @param  array<mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private function sanitize(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        $clean = [];

        foreach ($snapshot as $key => $value) {
            $key = (string) $key;

            if (in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
