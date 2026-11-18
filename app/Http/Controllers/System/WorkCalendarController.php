<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\WorkCalendarRequest;
use App\Models\WorkCalendar;
use App\Services\System\WorkCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkCalendarController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', WorkCalendar::class);

        return view('system.work-calendars.index', [
            'workCalendars' => WorkCalendar::query()->orderByDesc('date')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', WorkCalendar::class);

        return view('system.work-calendars.create');
    }

    public function store(WorkCalendarRequest $request, WorkCalendarService $service): RedirectResponse
    {
        $this->authorize('create', WorkCalendar::class);

        $service->create($request->validated());

        return redirect()
            ->route('system.work-calendars.index')
            ->with('status', 'Entri kalender kerja berhasil ditambahkan.');
    }

    public function edit(WorkCalendar $workCalendar): View
    {
        $this->authorize('update', $workCalendar);

        return view('system.work-calendars.edit', ['workCalendar' => $workCalendar]);
    }

    public function update(WorkCalendarRequest $request, WorkCalendar $workCalendar, WorkCalendarService $service): RedirectResponse
    {
        $this->authorize('update', $workCalendar);

        $service->update($workCalendar, $request->validated());

        return redirect()
            ->route('system.work-calendars.index')
            ->with('status', 'Entri kalender kerja berhasil diperbarui.');
    }

    public function destroy(WorkCalendar $workCalendar, WorkCalendarService $service): RedirectResponse
    {
        $this->authorize('delete', $workCalendar);

        $service->delete($workCalendar);

        return redirect()
            ->route('system.work-calendars.index')
            ->with('status', 'Entri kalender kerja berhasil dihapus.');
    }
}
