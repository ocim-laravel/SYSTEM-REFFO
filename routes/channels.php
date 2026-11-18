<?php

use App\Services\System\RealtimeService;
use Illuminate\Support\Facades\Broadcast;

/*
 * Kanal privat MOD-10 (AC-10-6). Nama polis di bawah TIDAK memakai awalan
 * "private-": Laravel membuang awalan tersebut ketika mencocokkan polis.
 */

Broadcast::channel('user.{id}', function ($user, $id): bool {
    return app(RealtimeService::class)->canListenToUserChannel($user, $id);
});

Broadcast::channel('role.{role}', function ($user, $role): bool {
    return app(RealtimeService::class)->canListenToRoleChannel($user, $role);
});

Broadcast::channel('division.{code}', function ($user, $code): bool {
    return app(RealtimeService::class)->canListenToDivisionChannel($user, $code);
});
