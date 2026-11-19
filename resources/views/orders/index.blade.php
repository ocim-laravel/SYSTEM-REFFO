@extends('layouts.app')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div>
            <h1 style="margin-top:0">Order</h1>
            <p class="muted" style="margin-top:0">Daftar order pelanggan (MOD-02).</p>
        </div>
        @can('create', App\Models\Order::class)
            <a href="{{ route('orders.create') }}" class="btn">Buat Order</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('orders.index') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end;margin-bottom:16px">
        <div class="field" style="margin:0">
            <label for="filter-status">Status</label>
            <select id="filter-status" name="status">
                <option value="">Semua status</option>
                @foreach (App\Enums\OrderStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin:0">
            <label for="filter-customer">Pelanggan</label>
            <select id="filter-customer" name="customer_id">
                <option value="">Semua pelanggan</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string)($filters['customer_id'] ?? '') === (string)$customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin:0">
            <label for="filter-from">Tanggal dari</label>
            <input id="filter-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="field" style="margin:0">
            <label for="filter-to">Tanggal sampai</label>
            <input id="filter-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn">Filter</button>
            <a href="{{ route('orders.index') }}" class="btn secondary">Reset</a>
        </div>
    </form>

    <p class="muted">{{ $orders->total() }} order ditemukan.</p>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Pelanggan</th>
                <th>Status</th>
                <th>Deadline</th>
                <th>Total</th>
                <th>Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td><strong>{{ $order->code }}</strong></td>
                    <td>{{ $order->customer?->name ?? '-' }}</td>
                    <td>{{ $order->status->label() }}</td>
                    <td>{{ $order->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>Rp {{ number_format((float) $order->total, 2, ',', '.') }}</td>
                    <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td><a href="{{ route('orders.show', $order) }}" class="btn small secondary">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Belum ada order.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px">{{ $orders->links() }}</div>
@endsection
