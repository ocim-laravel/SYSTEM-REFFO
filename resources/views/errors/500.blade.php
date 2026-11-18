<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kesalahan Server — {{ config('app.name', 'Sistem Reffo') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 0; background: #f3f4f6; color: #111827; }
        .wrap { max-width: 560px; margin: 10vh auto; padding: 24px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 32px; text-align: center; }
        .code { font-size: 48px; font-weight: 700; color: #b91c1c; margin: 0; }
        .btn { display: inline-block; margin-top: 18px; background: #111827; color: #fff; padding: 10px 14px; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <p class="code">500</p>
        <h1>Terjadi kesalahan pada server</h1>
        <p>Silakan muat ulang halaman. Jika masalah berlanjut, hubungi administrator.</p>
        <a class="btn" href="{{ url('/') }}">Kembali ke beranda</a>
    </div>
</div>
</body>
</html>
