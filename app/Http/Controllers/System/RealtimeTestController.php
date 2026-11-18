<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\RealtimeTestRequest;
use App\Services\System\RealtimeService;
use Illuminate\Http\JsonResponse;

class RealtimeTestController extends Controller
{
    public function __invoke(RealtimeTestRequest $request, RealtimeService $realtime): JsonResponse
    {
        $event = $realtime->sendTestNotification(
            $request->user(),
            (string) $request->input('message', 'Notifikasi realtime berhasil dikirim.'),
        );

        return response()->json([
            'ok' => true,
            'message' => $event->message,
            'channels' => array_map(fn ($channel): string => (string) $channel, $event->broadcastOn()),
        ]);
    }
}
