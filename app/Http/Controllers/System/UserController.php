<?php

namespace App\Http\Controllers\System;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\ResetUserPasswordRequest;
use App\Http\Requests\System\UserRequest;
use App\Models\Division;
use App\Models\User;
use App\Services\System\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('system.users.index', [
            'users' => User::query()->with('division')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('system.users.create', $this->formData());
    }

    public function store(UserRequest $request, UserService $service): RedirectResponse
    {
        $this->authorize('create', User::class);
        $service->create($request->validated(), $request->user(), $this->meta($request));

        return redirect()->route('system.users.index')->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('system.users.edit', $this->formData() + ['user' => $user]);
    }

    public function update(UserRequest $request, User $user, UserService $service): RedirectResponse
    {
        $this->authorize('update', $user);
        $service->update($user, $request->validated(), $request->user(), $this->meta($request));

        return redirect()->route('system.users.index')->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user, UserService $service, Request $request): RedirectResponse
    {
        $this->authorize('delete', $user);
        $service->delete($user, $request->user(), $this->meta($request));

        return redirect()->route('system.users.index')->with('status', 'Pengguna berhasil dihapus.');
    }

    public function toggleActive(Request $request, User $user, UserService $service): RedirectResponse
    {
        $this->authorize('toggleActive', $user);
        $service->setActive($user, ! $user->is_active, $request->user(), $this->meta($request));

        return redirect()->route('system.users.index')->with('status', 'Status pengguna berhasil diperbarui.');
    }

    public function editPassword(User $user): View
    {
        $this->authorize('resetPassword', $user);

        return view('system.users.reset-password', ['user' => $user]);
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user, UserService $service): RedirectResponse
    {
        $this->authorize('resetPassword', $user);
        $service->resetPassword($user, $request->validated('password'), $request->user(), $this->meta($request));

        return redirect()->route('system.users.index')->with('status', 'Password pengguna berhasil direset.');
    }

    /**
     * @return array{roles: list<UserRole>, divisions: Collection<int, Division>}
     */
    private function formData(): array
    {
        return [
            'roles' => UserRole::cases(),
            'divisions' => Division::query()->orderBy('name')->get(),
        ];
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
