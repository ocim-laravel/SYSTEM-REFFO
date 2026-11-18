@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Dashboard</h1>
    <p>
        Selamat datang, <strong>{{ auth()->user()->name }}</strong>.
    </p>
    <p class="muted">
        Role: {{ auth()->user()->roleEnum()?->label() ?? '-' }}
        @if (auth()->user()->division)
            · Divisi: {{ auth()->user()->division->name }}
        @endif
    </p>
    <p class="muted" style="margin-bottom:0">
        Halaman ini masih placeholder pada PH-00. Menu di samping hanya menampilkan
        bagian yang menjadi hak akses role Anda.
    </p>
@endsection
