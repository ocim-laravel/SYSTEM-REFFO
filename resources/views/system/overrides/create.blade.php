@extends('layouts.app')

@push('head')
    <style>
        select, textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
    </style>
@endpush

@section('content')
    <h1 style="margin-top:0">Catat Override</h1>

    <p class="muted">Override hanya dapat dilakukan Owner. Alasan wajib diisi (BR-18).</p>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('system.overrides.store') }}">
        @csrf

        <div class="field">
            <label for="rule">Aturan yang di-override</label>
            <select id="rule" name="rule" required>
                @foreach ($rules as $rule)
                    <option value="{{ $rule->value }}" @selected(old('rule') === $rule->value)>
                        {{ $rule->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="target_type">Target (opsional)</label>
            <input id="target_type" type="text" name="target_type" value="{{ old('target_type') }}" maxlength="255" placeholder="Mis. App\Models\User">
        </div>

        <div class="field">
            <label for="target_id">ID target (opsional)</label>
            <input id="target_id" type="number" name="target_id" value="{{ old('target_id') }}" min="1">
        </div>

        <div class="field">
            <label for="before">Sebelum (JSON, opsional)</label>
            <textarea id="before" name="before" rows="3" placeholder='{"is_active": false}'>{{ old('before') }}</textarea>
        </div>

        <div class="field">
            <label for="after">Sesudah (JSON, opsional)</label>
            <textarea id="after" name="after" rows="3" placeholder='{"is_active": true}'>{{ old('after') }}</textarea>
        </div>

        <div class="field">
            <label for="reason">Alasan override <span style="color:#b91c1c">*</span></label>
            <textarea id="reason" name="reason" rows="3" required maxlength="1000">{{ old('reason') }}</textarea>
            <div class="muted">Wajib. Tanpa alasan, override ditolak.</div>
        </div>

        <button type="submit" class="btn">Simpan Override</button>
        <a href="{{ route('system.overrides.index') }}" class="btn secondary">Batal</a>
    </form>
@endsection
