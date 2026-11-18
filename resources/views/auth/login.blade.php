@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Login</h1>
    <p class="muted">Silakan masuk menggunakan akun admin atau operator.</p>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>
        </div>
        <div class="field" style="display:flex;align-items:center;gap:8px;">
            <input id="remember" type="checkbox" name="remember" value="1" style="width:auto">
            <label for="remember" style="margin:0;font-weight:normal">Ingat saya</label>
        </div>
        <button type="submit" class="btn">Masuk</button>
    </form>
@endsection
