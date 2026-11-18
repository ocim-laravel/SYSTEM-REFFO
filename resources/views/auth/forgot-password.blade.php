@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Lupa Password</h1>
    <p class="muted">Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang password.</p>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <button type="submit" class="btn">Kirim Tautan Reset</button>
        <a href="{{ route('login') }}" class="btn secondary">Kembali ke Login</a>
    </form>
@endsection
