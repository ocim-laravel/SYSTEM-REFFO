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
        .muted { color: #6b7280; font-size: 14px; }
        .alert { padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        input[type="email"], input[type="password"], input[type="text"] { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        .field { margin-bottom: 14px; }
        ul#realtime-log { margin-top: 12px; padding-left: 18px; }
        ul#realtime-log li { margin-bottom: 6px; font-size: 14px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    </style>
    @stack('head')
</head>
<body>
    <div class="container">
        @auth
            <div class="nav">
                <div>
                    <strong>{{ config('app.name') }}</strong>
                    <div class="muted">Masuk sebagai: {{ auth()->user()->name }} ({{ auth()->user()->role }})</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn secondary">Logout</button>
                </form>
            </div>
        @endauth

        <div class="card">
            @yield('content')
        </div>
    </div>
    @stack('scripts')
</body>
</html>
