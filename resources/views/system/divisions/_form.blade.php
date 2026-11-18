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
        <label for="code">Kode</label>
        <input id="code" type="text" name="code" value="{{ old('code', $division->code ?? '') }}" maxlength="20" required>
        <div class="muted">Contoh: ADM, DSN, PRD.</div>
    </div>

    <div class="field">
        <label for="name">Nama Divisi</label>
        <input id="name" type="text" name="name" value="{{ old('name', $division->name ?? '') }}" maxlength="100" required>
    </div>

    <div class="field">
        <input type="hidden" name="is_active" value="0">
        <label style="font-weight:normal">
            <input id="is_active" type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $division->is_active ?? true))>
            Aktif
        </label>
    </div>

    <button type="submit" class="btn">Simpan</button>
    <a href="{{ route('system.divisions.index') }}" class="btn secondary">Batal</a>
</form>
