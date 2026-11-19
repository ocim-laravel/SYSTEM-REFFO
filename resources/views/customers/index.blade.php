@extends('layouts.app')

@push('head')
    <style>
        .customer-filter { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; align-items: end; }
        .customer-filter .field { margin-bottom: 0; }
        .customer-filter select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; background: #fff; }
        .customer-filter .filter-actions { display: flex; gap: 8px; }
    </style>
@endpush

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div>
            <h1 style="margin-top:0">Pelanggan</h1>
            <p class="muted" style="margin-top:0">Data pelanggan yang dipakai seluruh modul (MOD-01).</p>
        </div>
        @can('create', App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" class="btn">Tambah Pelanggan</a>
        @endcan
    </div>

    @if ($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" action="{{ route('customers.index') }}" class="customer-filter" style="margin-bottom:16px">
        <div class="field">
            <label for="filter-name">Nama</label>
            <input id="filter-name" type="text" name="name" value="{{ $filters['name'] ?? '' }}" maxlength="255">
        </div>
        <div class="field">
            <label for="filter-code">Kode</label>
            <input id="filter-code" type="text" name="code" value="{{ $filters['code'] ?? '' }}" maxlength="32">
        </div>
        <div class="field">
            <label for="filter-phone">Telepon</label>
            <input id="filter-phone" type="text" name="phone" value="{{ $filters['phone'] ?? '' }}" maxlength="32">
        </div>
        <div class="field">
            <label for="filter-type">Tipe</label>
            <select id="filter-type" name="type">
                <option value="">Semua tipe</option>
                @foreach (App\Enums\CustomerType::cases() as $typeOption)
                    <option value="{{ $typeOption->value }}" @selected(($filters['type'] ?? '') === $typeOption->value)>
                        {{ $typeOption->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="filter-status">Status</label>
            <select id="filter-status" name="status">
                <option value="">Semua status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn">Cari</button>
            <a href="{{ route('customers.index') }}" class="btn secondary">Reset</a>
        </div>
    </form>

    <p class="muted">{{ $customers->total() }} pelanggan ditemukan.</p>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Tipe</th>
                <th>Telepon</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td><strong>{{ $customer->code }}</strong></td>
                    <td>
                        <a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>
                    </td>
                    <td>{{ $customer->type->label() }}</td>
                    <td>{{ $customer->phone ?: '-' }}</td>
                    <td>{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        <a href="{{ route('customers.show', $customer) }}" class="btn small secondary">Detail</a>
                        @can('update', $customer)
                            <a href="{{ route('customers.edit', $customer) }}" class="btn small secondary">Ubah</a>
                        @endcan
                        @can('delete', $customer)
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline-form" onsubmit="return confirm('Hapus pelanggan ini secara permanen?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="reason" value="Penghapusan pelanggan oleh Owner">
                                <button type="submit" class="btn small danger">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">Tidak ada pelanggan yang cocok dengan pencarian.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px">
        {{ $customers->links() }}
    </div>
@endsection
