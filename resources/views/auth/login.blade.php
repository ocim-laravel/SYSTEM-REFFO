@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Login</h1>
    <p class="muted">Silakan masuk menggunakan akun Anda.</p>

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

    <p class="muted" style="margin-bottom:0">
        <a href="{{ route('password.request') }}">Lupa password?</a>
    </p>

    @if (\App\Services\System\DemoAccountService::isEnabled())
        <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0">

        <h3 style="margin-bottom:4px">Akun Demo</h3>
        <p class="muted" style="margin-top:0">
            Khusus lingkungan pengembangan — tabel ini tidak tampil di production.
            Klik <strong>Isi Form</strong> untuk mengisi email &amp; password otomatis,
            atau <strong>Salin</strong> untuk menyalin nilainya.
        </p>

        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach (\App\Services\System\DemoAccountService::accounts() as $account)
                    <tr>
                        <td>{{ \App\Enums\UserRole::from($account['role'])->label() }}</td>
                        <td>
                            <code>{{ $account['email'] }}</code>
                            <button type="button"
                                    class="btn small secondary"
                                    title="Salin email"
                                    data-copy="{{ $account['email'] }}">Salin</button>
                        </td>
                        <td>
                            <code>{{ \App\Services\System\DemoAccountService::PASSWORD }}</code>
                            <button type="button"
                                    class="btn small secondary"
                                    title="Salin password"
                                    data-copy="{{ \App\Services\System\DemoAccountService::PASSWORD }}">Salin</button>
                        </td>
                        <td>
                            <button type="button"
                                    class="btn small secondary"
                                    data-fill-email="{{ $account['email'] }}"
                                    data-fill-password="{{ \App\Services\System\DemoAccountService::PASSWORD }}">
                                Isi Form
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @push('scripts')
            <script>
                (function () {
                    function copyText(value, button) {
                        var done = function () {
                            var original = button.textContent;
                            button.textContent = 'Tersalin';
                            setTimeout(function () {
                                button.textContent = original;
                            }, 1200);
                        };

                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(value).then(done, function () {});
                            return;
                        }

                        var helper = document.createElement('textarea');
                        helper.value = value;
                        helper.setAttribute('readonly', 'readonly');
                        helper.style.position = 'fixed';
                        helper.style.top = '-1000px';
                        helper.style.opacity = '0';
                        document.body.appendChild(helper);
                        helper.select();

                        try {
                            if (document.execCommand('copy')) {
                                done();
                            }
                        } catch (error) {
                            // Clipboard tidak tersedia: teks tetap bisa disalin manual.
                        }

                        document.body.removeChild(helper);
                    }

                    function fillLogin(email, password) {
                        document.getElementById('email').value = email;
                        document.getElementById('password').value = password;
                        document.getElementById('email').focus();
                    }

                    document.addEventListener('click', function (event) {
                        var copyButton = event.target.closest('[data-copy]');
                        if (copyButton) {
                            copyText(copyButton.getAttribute('data-copy'), copyButton);
                            return;
                        }

                        var fillButton = event.target.closest('[data-fill-email]');
                        if (fillButton) {
                            fillLogin(fillButton.getAttribute('data-fill-email'), fillButton.getAttribute('data-fill-password'));
                        }
                    });
                })();
            </script>
        @endpush
    @endif
@endsection
