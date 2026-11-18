<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\SettingsRequest;
use App\Models\Setting;
use App\Services\System\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        return view('system.settings.index', ['settings' => $this->settings->all()]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $this->settings->update(
            $request->validated(),
            $request->file('company_logo'),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('system.settings.index')->with('status', 'Pengaturan sistem berhasil disimpan.');
    }
}
