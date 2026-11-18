<?php

namespace App\Services\System;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogService
{
    /**
     * Catat satu aktivitas penting ke audit log (BR-17).
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $action,
        ?User $actor = null,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    public function recordLogin(User $user, ?string $ip = null, ?string $userAgent = null): AuditLog
    {
        return $this->record(
            action: 'login',
            actor: $user,
            modelType: User::class,
            modelId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
        );
    }
}
