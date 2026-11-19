@extends('layouts.app')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
        <div>
            <h1 style="margin-top:0">Detail Order {{ $order->code }}</h1>
            <p class="muted" style="margin-top:0">
                Pelanggan: {{ $order->customer?->name ?? '-' }} · Status: <strong>{{ $order->status->label() }}</strong>
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @can('validate', $order)
                @if ($order->status === App\Enums\OrderStatus::PendingValidation)
                    <form method="POST" action="{{ route('orders.validate', $order) }}" onsubmit="return confirm('Validasi order ini? Semua item akan masuk antrean desain.')" class="inline-form">
                        @csrf
                        <button type="submit" class="btn">Validasi Order</button>
                    </form>
                @endif
            @endcan
            <a href="{{ route('orders.index') }}" class="btn secondary">Kembali</a>
        </div>
    </div>

    <h2>Informasi Order</h2>
    <table>
        <tbody>
            <tr><th>Kode</th><td>{{ $order->code }}</td></tr>
            <tr><th>Pelanggan</th><td>{{ $order->customer?->code }} - {{ $order->customer?->name }}</td></tr>
            <tr><th>Status</th><td>{{ $order->status->label() }}</td></tr>
            <tr><th>Prioritas</th><td>{{ $order->priority->label() }}</td></tr>
            <tr><th>Deadline Order</th><td>{{ $order->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
            <tr><th>Subtotal</th><td>Rp {{ number_format((float) $order->subtotal, 2, ',', '.') }}</td></tr>
            <tr><th>Diskon</th><td>Rp {{ number_format((float) $order->discount, 2, ',', '.') }}</td></tr>
            <tr><th>Pajak</th><td>Rp {{ number_format((float) $order->tax, 2, ',', '.') }}</td></tr>
            <tr><th>Biaya Kirim/Pasang</th><td>Rp {{ number_format((float) $order->delivery_fee, 2, ',', '.') }} (sekali per order)</td></tr>
            <tr><th>Total</th><td><strong>Rp {{ number_format((float) $order->total, 2, ',', '.') }}</strong></td></tr>
            <tr><th>Catatan</th><td>{{ $order->notes ?: '-' }}</td></tr>
            <tr><th>Dibuat oleh</th><td>{{ $order->creator?->name ?? '-' }} ({{ $order->created_at?->format('d/m/Y H:i') }})</td></tr>
            <tr><th>Divalidasi oleh</th><td>{{ $order->validator?->name ?? '-' }} {{ $order->validated_at ? '(' . $order->validated_at->format('d/m/Y H:i') . ')' : '' }}</td></tr>
        </tbody>
    </table>

    <h2>Item</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Total</th>
                <th>Prioritas</th>
                <th>Deadline</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td>{{ $item->seq }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->category->label() }}</td>
                    <td>{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }} {{ $item->unit }}</td>
                    <td>Rp {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                    <td>Rp {{ number_format((float) $item->total, 2, ',', '.') }}</td>
                    <td>{{ $item->priority->label() }}</td>
                    <td>{{ $item->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $item->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">Belum ada item.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Riwayat Status Item</h2>
    @forelse ($order->items as $item)
        <h3 style="margin-bottom:4px">{{ $item->seq }}. {{ $item->title }}</h3>
        <table style="margin-bottom:12px">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Dari</th>
                    <th>Ke</th>
                    <th>Oleh</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($item->statusHistories as $history)
                    <tr>
                        <td>{{ $history->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $history->from_status?->label() ?? '-' }}</td>
                        <td>{{ $history->to_status->label() }}</td>
                        <td>{{ $history->changedBy?->name ?? '-' }}</td>
                        <td>{{ $history->note ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada riwayat perubahan status.</td></tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <p class="muted">Belum ada item.</p>
    @endforelse
@endsection
