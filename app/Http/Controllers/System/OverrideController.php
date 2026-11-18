<?php

namespace App\Http\Controllers\System;

use App\Enums\OverridableRule;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\OverrideRequest;
use App\Models\AuditLog;
use App\Services\System\OverrideService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverrideController extends Controller
{
    public function index(): View
    {
        $this->authorize('system.override');

        return view('system.overrides.index', [
            'logs' => AuditLog::query()
                ->where('action', 'like', 'override.%')
                ->with('user')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('system.override');

        return view('system.overrides.create', ['rules' => OverridableRule::cases()]);
    }

    public function store(OverrideRequest $request, OverrideService $service): RedirectResponse
    {
        $this->authorize('system.override');

        $service->override($request->user(), $request->validated(), $this->meta($request));

        return redirect()->route('system.overrides.index')->with('status', 'Override berhasil dicatat.');
    }

    /**
     * @return array{ip: string|null, user_agent: string|null}
     */
    private function meta(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
