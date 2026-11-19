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

    @if (isset($customer))
        <div class="field">
            <label>Kode Pelanggan</label>
            <div class="muted">{{ $customer->code }} (kode otomatis, tidak dapat diubah)</div>
        </div>
    @else
        <div class="field">
            <label>Kode Pelanggan</label>
            <div class="muted">Dibuat otomatis setelah disimpan (format CUS-TAHUN-0001).</div>
        </div>
    @endif

    <div class="field">
        <label for="name">Nama</label>
        <input id="name" type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required maxlength="255">
    </div>

    <div class="field">
        <label for="type">Tipe</label>
        <select id="type" name="type" required>
            @foreach (App\Enums\CustomerType::cases() as $typeOption)
                <option value="{{ $typeOption->value }}" @selected(old('type', $customer->type?->value ?? App\Enums\CustomerType::Individual->value) === $typeOption->value)>
                    {{ $typeOption->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="phone">Telepon</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" maxlength="32">
    </div>

    <div class="field">
        <label for="whatsapp">WhatsApp</label>
        <input id="whatsapp" type="text" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp ?? '') }}" maxlength="32">
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" maxlength="255">
    </div>

    <div class="field">
        <label for="address">Alamat</label>
        <textarea id="address" name="address" rows="3" maxlength="1000">{{ old('address', $customer->address ?? '') }}</textarea>
    </div>

    <div class="field">
        <label for="notes">Catatan Internal</label>
        <textarea id="notes" name="notes" rows="3" maxlength="1000">{{ old('notes', $customer->notes ?? '') }}</textarea>
    </div>

    <div class="field">
        <input type="hidden" name="is_active" value="0">
        <label style="font-weight:normal">
            <input id="is_active" type="checkbox" name="is_active" value="1" style="width:auto" @checked(old('is_active', $customer->is_active ?? true))>
            Aktif
        </label>
        <div class="muted">Pelanggan nonaktif tidak dapat dipakai pada order baru.</div>
    </div>

    <button type="submit" class="btn">Simpan</button>
    <a href="{{ route('customers.index') }}" class="btn secondary">Batal</a>
</form>
