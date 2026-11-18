@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Reset Password</h1>
    <p class="muted">Masukkan password baru untuk akun Anda.</p>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password Baru</label>
            <input id="password" type="password" name="password" required>
        </div>
        <div class="field">
            <label for="password_confirmation">Konfirmasi Password Baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn">Simpan Password Baru</button>
    </form>
@endsection
