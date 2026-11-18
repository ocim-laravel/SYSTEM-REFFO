@extends('layouts.app')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div>
            <h1 style="margin-top:0">Pengaturan Sistem</h1>
            <p class="muted" style="margin-top:0">Nama perusahaan, logo, dan jam kerja. Hanya Owner yang dapat mengubah.</p>
        </div>
    </div>

    @if (! empty($settings['company_logo']))
        <div style="margin-bottom:16px">
            <img src="{{ \Illuminate\Support\Facades\Storage::disk(\App\Services\System\SettingService::LOGO_DISK)->url($settings['company_logo']) }}"
                 alt="Logo {{ $settings['company_name'] }}"
                 style="max-height:64px;max-width:220px;border:1px solid #e5e7eb;border-radius:6px;padding:6px;background:#fff">
        </div>
    @endif

    <form method="POST" action="{{ route('system.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert error">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="field">
            <label for="company_name">Nama Perusahaan</label>
            <input id="company_name" type="text" name="company_name"
                   value="{{ old('company_name', $settings['company_name']) }}" maxlength="120" required>
        </div>

        <div class="field">
            <label for="company_logo">Logo Perusahaan</label>
            <input id="company_logo" type="file" name="company_logo" accept="image/png,image/jpeg,image/webp">
            <div class="muted">Format PNG, JPG, atau WEBP, maksimal 2 MB.</div>
        </div>

        @if (! empty($settings['company_logo']))
            <div class="field">
                <label style="font-weight:normal">
                    <input type="checkbox" name="remove_logo" value="1" style="width:auto" @checked(old('remove_logo', false))>
                    Hapus logo yang tersimpan
                </label>
            </div>
        @endif

        <div style="display:flex;gap:16px">
            <div class="field" style="flex:1">
                <label for="work_hours_start">Jam Masuk</label>
                <input id="work_hours_start" type="time" name="work_hours_start"
                       value="{{ old('work_hours_start', $settings['work_hours_start']) }}" required>
            </div>
            <div class="field" style="flex:1">
                <label for="work_hours_end">Jam Pulang</label>
                <input id="work_hours_end" type="time" name="work_hours_end"
                       value="{{ old('work_hours_end', $settings['work_hours_end']) }}" required>
            </div>
        </div>

        <button type="submit" class="btn">Simpan Pengaturan</button>
    </form>
@endsection
