<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\DivisionRequest;
use App\Models\Division;
use App\Services\System\DivisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DivisionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Division::class);

        return view('system.divisions.index', [
            'divisions' => Division::query()->withCount('users')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Division::class);

        return view('system.divisions.create');
    }

    public function store(DivisionRequest $request, DivisionService $service): RedirectResponse
    {
        $this->authorize('create', Division::class);

        $service->create($request->validated());

        return redirect()
            ->route('system.divisions.index')
            ->with('status', 'Divisi berhasil ditambahkan.');
    }

    public function edit(Division $division): View
    {
        $this->authorize('update', $division);

        return view('system.divisions.edit', ['division' => $division]);
    }

    public function update(DivisionRequest $request, Division $division, DivisionService $service): RedirectResponse
    {
        $this->authorize('update', $division);

        $service->update($division, $request->validated());

        return redirect()
            ->route('system.divisions.index')
            ->with('status', 'Divisi berhasil diperbarui.');
    }

    public function destroy(Division $division, DivisionService $service): RedirectResponse
    {
        $this->authorize('delete', $division);

        $service->delete($division);

        return redirect()
            ->route('system.divisions.index')
            ->with('status', 'Divisi berhasil dihapus.');
    }
}
