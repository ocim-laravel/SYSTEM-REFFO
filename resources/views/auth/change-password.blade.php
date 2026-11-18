@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Ubah Password</h1>
    <p class="muted">
        @if (auth()->user()->must_change_password)
            Demi keamanan, Anda wajib mengganti password sebelum melanjutkan.
        @else
            Masukkan password saat ini lalu tentukan password baru.
        @endif
    </p>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf
        @method('PUT')

        <div class="field">
            <label for="current_password">Password Saat Ini</label>
            <input id="current_password" type="password" name="current_password" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password Baru</label>
            <input id="password" type="password" name="password" required>
        </div>
        <div class="field">
            <label for="password_confirmation">Konfirmasi Password Baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn">Simpan Password</button>
    </form>
@endsection
