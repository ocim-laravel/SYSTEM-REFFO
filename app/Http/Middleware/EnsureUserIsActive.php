<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak pengguna yang dinonaktifkan saat sesi masih berlaku (04-architecture §4.5).
 */
class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->is_active) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Akun Anda tidak aktif.'], Response::HTTP_FORBIDDEN);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
    }
}
