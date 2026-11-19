@extends('layouts.app')

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
                    <td colspan="6" class="muted">Belum ada pelanggan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px">
        {{ $customers->links() }}
    </div>
@endsection
