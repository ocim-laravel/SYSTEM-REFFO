@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Reset Password — {{ $user->name }}</h1>
    <p class="muted">Password baru akan berlaku segera dan wajib diganti pengguna saat login berikutnya.</p>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('system.users.reset-password', $user) }}">
        @csrf
        @method('PUT')

        <div class="field">
            <label for="password">Password Baru</label>
            <input id="password" type="password" name="password" minlength="8" required>
        </div>
        <div class="field">
            <label for="password_confirmation">Konfirmasi Password Baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>
        </div>

        <button type="submit" class="btn">Reset Password</button>
        <a href="{{ route('system.users.index') }}" class="btn secondary">Batal</a>
    </form>
@endsection
