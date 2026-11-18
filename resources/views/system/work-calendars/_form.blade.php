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
        <label for="date">Tanggal</label>
        <input id="date" type="date" name="date" value="{{ old('date', isset($workCalendar) ? $workCalendar->date->toDateString() : '') }}" required>
    </div>

    <div class="field">
        <input type="hidden" name="is_workday" value="0">
        <label style="font-weight:normal">
            <input id="is_workday" type="checkbox" name="is_workday" value="1" style="width:auto" @checked(old('is_workday', $workCalendar->is_workday ?? true))>
            Hari kerja
        </label>
        <div class="muted">Nonaktifkan untuk menandai tanggal sebagai libur.</div>
    </div>

    <div class="field">
        <label for="note">Catatan</label>
        <input id="note" type="text" name="note" value="{{ old('note', $workCalendar->note ?? '') }}" maxlength="255">
    </div>

    <button type="submit" class="btn">Simpan</button>
    <a href="{{ route('system.work-calendars.index') }}" class="btn secondary">Batal</a>
</form>
