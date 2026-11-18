<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sistem Reffo') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 0; background: #f3f4f6; color: #111827; }
        .container { max-width: 960px; margin: 0 auto; padding: 24px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .btn { display: inline-block; background: #111827; color: #fff; border: none; padding: 10px 14px; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .btn.secondary { background: #6b7280; }
        .btn.danger { background: #b91c1c; }
        .btn.small { padding: 6px 10px; font-size: 13px; }
        .muted { color: #6b7280; font-size: 14px; }
        .alert { padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        input[type="email"], input[type="password"], input[type="text"] { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        .field { margin-bottom: 14px; }
        ul#realtime-log { margin-top: 12px; padding-left: 18px; }
        ul#realtime-log li { margin-bottom: 6px; font-size: 14px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }

        .app-shell { display: flex; min-height: 100vh; }
        .sidebar { width: 232px; flex: 0 0 232px; background: #fff; border-right: 1px solid #e5e7eb; padding: 16px; box-sizing: border-box; }
        .sidebar-brand { padding-bottom: 14px; border-bottom: 1px solid #e5e7eb; margin-bottom: 14px; }
        .sidebar-brand .muted { margin-top: 4px; }
        .badge { display: inline-block; margin-top: 8px; background: #eef2ff; color: #3730a3; border-radius: 999px; padding: 2px 10px; font-size: 12px; font-weight: 600; }
        .menu-section { margin-bottom: 16px; }
        .menu-title { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; margin-bottom: 6px; }
        .menu-link { display: block; padding: 8px 10px; border-radius: 6px; color: #374151; text-decoration: none; font-size: 14px; }
        .menu-link:hover { background: #f3f4f6; }
        .menu-link.active { background: #111827; color: #fff; }
        .menu-link.disabled { color: #9ca3af; cursor: default; }
        .main { flex: 1; padding: 24px; box-sizing: border-box; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { color: #6b7280; font-weight: 600; }
        .inline-form { display: inline; }
    </style>
    @stack('head')
</head>
<body>
    @auth
        <div class="app-shell">
            @include('layouts.partials.sidebar')

            <main class="main">
                <div class="topbar">
                    <div>
                        <strong>{{ config('app.name') }}</strong>
                        <div class="muted">
                            Masuk sebagai: {{ auth()->user()->name }}
                            ({{ auth()->user()->roleEnum()?->label() ?? '-' }})
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn secondary">Logout</button>
                    </form>
                </div>

                <div class="card">
                    @if (session('status'))
                        <div class="alert success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    @else
        <div class="container">
            <div class="card">
                @yield('content')
            </div>
        </div>
    @endauth
    @stack('scripts')
</body>
</html>
