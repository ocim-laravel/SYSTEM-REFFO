<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => $request->string('password')->value(),
            'must_change_password' => false,
        ])->save();

        return redirect()
            ->to($this->dashboardRouteFor($user))
            ->with('status', 'Password berhasil diubah.');
    }

    private function dashboardRouteFor(User $user): string
    {
        return match ($user->roleEnum()) {
            UserRole::Admin => route('admin.dashboard'),
            default => route('dashboard'),
        };
    }
}
