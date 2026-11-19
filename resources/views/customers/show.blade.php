@extends('layouts.app')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div>
            <h1 style="margin-top:0">{{ $customer->name }}</h1>
            <p class="muted" style="margin-top:0">{{ $customer->code }} · {{ $customer->type->label() }} · {{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</p>
        </div>
        @can('update', $customer)
            <a href="{{ route('customers.edit', $customer) }}" class="btn">Ubah Data</a>
        @endcan
    </div>

    <h2>Profil</h2>
    <table>
        <tbody>
            <tr><th>Kode</th><td>{{ $customer->code }}</td></tr>
            <tr><th>Nama</th><td>{{ $customer->name }}</td></tr>
            <tr><th>Tipe</th><td>{{ $customer->type->label() }}</td></tr>
            <tr><th>Telepon</th><td>{{ $customer->phone ?: '-' }}</td></tr>
            <tr><th>WhatsApp</th><td>{{ $customer->whatsapp ?: '-' }}</td></tr>
            <tr><th>Email</th><td>{{ $customer->email ?: '-' }}</td></tr>
            <tr><th>Alamat</th><td>{{ $customer->address ?: '-' }}</td></tr>
            <tr><th>Catatan Internal</th><td>{{ $customer->notes ?: '-' }}</td></tr>
            <tr><th>Status</th><td>{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</td></tr>
            <tr><th>Dicatat oleh</th><td>{{ $customer->creator?->name ?? '-' }}</td></tr>
        </tbody>
    </table>

    <h2>Riwayat Order</h2>
    <p class="muted">Belum ada order pada fase ini (MOD-02 dikerjakan di PH-02).</p>

    <p style="margin-top:16px">
        <a href="{{ route('customers.index') }}" class="btn secondary">Kembali ke Daftar</a>
    </p>
@endsection
