@if ($errors->any())
    <div class="alert error">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="field">
        <label for="name">Nama</label>
        <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" maxlength="100" required>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" maxlength="255" required>
    </div>

    <div class="field">
        <label for="role">Peran</label>
        <select id="role" name="role" required>
            @foreach ($roles as $option)
                <option value="{{ $option->value }}" @selected(old('role', $user?->roleEnum()?->value ?? '') === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="division_id">Divisi</label>
        <select id="division_id" name="division_id">
            <option value="">Tanpa divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->id }}" @selected((string) old('division_id', $user->division_id ?? '') === (string) $division->id)>{{ $division->name }}</option>
            @endforeach
        </select>
    </div>

    @if ($user === null)
        <div class="field">
            <label for="password">Password Awal</label>
            <input id="password" type="password" name="password" minlength="8" required>
            <div class="muted">Minimal 8 karakter. Pengguna wajib menggantinya saat login pertama.</div>
        </div>
        <div class="field">
            <label for="password_confirmation">Konfirmasi Password Awal</label>
            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>
        </div>
    @else
        <div class="field">
            <input type="hidden" name="is_active" value="0">
            <label style="font-weight:normal">
                <input id="is_active" type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $user->is_active))>
                Aktif
            </label>
        </div>
        <p class="muted">Untuk mengganti password, gunakan tombol Reset Password pada daftar pengguna.</p>
    @endif

    <button type="submit" class="btn">Simpan</button>
    <a href="{{ route('system.users.index') }}" class="btn secondary">Batal</a>
</form>
