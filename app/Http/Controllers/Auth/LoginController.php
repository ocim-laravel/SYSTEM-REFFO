<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\System\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogs) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        if (! Auth::attempt($request->safe()->only(['email', 'password']), $request->boolean('remember'))) {
            $request->hitRateLimiter();

            return back()->withErrors([
                'email' => 'Email atau password tidak sesuai.',
            ])->onlyInput('email');
        }

        $request->clearRateLimiter();
        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Akun Anda nonaktif. Hubungi administrator.',
            ])->onlyInput('email');
        }

        $this->auditLogs->recordLogin($user, $request->ip(), $request->userAgent());

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended($this->dashboardRouteFor($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function dashboardRouteFor(User $user): string
    {
        return match ($user->roleEnum()) {
            UserRole::Admin => route('admin.dashboard'),
            default => route('dashboard'),
        };
    }
}
