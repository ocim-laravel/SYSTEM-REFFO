<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\System\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Notification::class);

        return view('notifications.index', [
            'notifications' => $this->notifications->allFor($request->user()),
        ]);
    }

    public function update(Notification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        $this->notifications->markAsRead($notification);

        return back()->with('status', 'Notifikasi ditandai sudah dibaca.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Notification::class);

        $this->notifications->markAllAsRead($request->user());

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
