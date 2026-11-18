@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Dashboard Admin</h1>
    <p>Selamat datang, <strong>{{ auth()->user()->name }}</strong>. Anda masuk sebagai <strong>admin</strong>.</p>

    <hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0">
    <h3 style="margin-bottom:6px">Uji Realtime (Reverb)</h3>
    <p class="muted" style="margin-top:0">Pastikan server Reverb berjalan (<code>php artisan reverb:start</code>) dan aset frontend aktif (<code>npm run dev</code> atau sudah di-build). Buka endpoint berikut di tab lain untuk mengirim event:</p>
    <p><code>{{ url('/broadcast-test?message=Halo%20Admin') }}</code></p>
    <p class="muted">Pesan yang diterima akan muncul di console browser dan daftar di bawah (jika Echo terhubung):</p>
    <ul id="realtime-log"></ul>
@endsection
