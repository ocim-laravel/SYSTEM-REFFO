<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ringkasan Pekerjaan Lapangan {{ $delivery->order?->code }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 32px; font-size: 13px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 24px 0 8px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 190px; }
        .signature { margin-top: 40px; display: flex; gap: 48px; }
        .signature div { flex: 1; border-top: 1px solid #111827; padding-top: 6px; }
        @media print { body { margin: 16px; } }
    </style>
</head>
<body>
    <h1>Ringkasan Pekerjaan Lapangan</h1>
    <p class="muted" style="margin-top:0">
        Order {{ $delivery->order?->code ?? '-' }} · Dicetak {{ now()->format('d/m/Y H:i') }}
    </p>

    <h2>Informasi Umum</h2>
    <table>
        <tbody>
            <tr><th>Pelanggan</th><td>{{ $delivery->order?->customer?->name ?? '-' }}</td></tr>
            <tr><th>Jenis Pekerjaan</th><td>{{ $delivery->type?->label() ?? '-' }}</td></tr>
            <tr><th>Jadwal</th><td>{{ $delivery->scheduled_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
            <tr><th>Alamat</th><td>{{ $delivery->address ?? '-' }}</td></tr>
            <tr><th>Petugas Lapangan</th><td>{{ $delivery->crewMembers()->pluck('name')->implode(', ') ?: '-' }}</td></tr>
            <tr><th>Berangkat</th><td>{{ $delivery->departed_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
            <tr><th>Tiba</th><td>{{ $delivery->arrived_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
            <tr><th>Status</th><td>{{ $delivery->status?->label() ?? '-' }}</td></tr>
            <tr><th>Selesai</th><td>{{ $delivery->completed_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $delivery->completer?->name ?? '-' }}</td></tr>
        </tbody>
    </table>

    <h2>Item</h2>
    <table>
        <thead>
            <tr>
                <th style="width:auto">Item</th>
                <th style="width:auto">Status</th>
                <th style="width:auto">Deadline</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($delivery->items as $deliveryItem)
                <tr>
                    <td>{{ $deliveryItem->orderItem?->title ?? '-' }}</td>
                    <td>{{ $deliveryItem->orderItem?->status?->label() ?? '-' }}</td>
                    <td>{{ $deliveryItem->orderItem?->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Belum ada item.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Bukti Penyelesaian</h2>
    <table>
        <thead>
            <tr>
                <th style="width:auto">Foto</th>
                <th style="width:auto">Catatan</th>
                <th style="width:auto">Diunggah Oleh</th>
                <th style="width:auto">Dikonfirmasi Oleh</th>
                <th style="width:auto">Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($delivery->proofs as $proof)
                <tr>
                    <td>{{ $proof->photo_path }}</td>
                    <td>{{ $proof->note ?? '-' }}</td>
                    <td>{{ $proof->uploader?->name ?? '-' }}</td>
                    <td>{{ $proof->confirmer?->name ?? '-' }}</td>
                    <td>{{ $proof->uploaded_at?->format('d/m/Y H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada bukti.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Biaya</h2>
    <table>
        <tbody>
            <tr><th>Total Biaya Kirim/Pasang Order</th><td>Rp {{ number_format((float) $chargedFee, 0, ',', '.') }}</td></tr>
            <tr><th>Ditagih pada Jadwal Ini</th><td>Rp {{ number_format((float) $delivery->fee, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>
    <p class="muted">
        Biaya kirim/pasang standar dikenakan satu kali per order (BR-14).
    </p>

    <div class="signature">
        <div>Petugas Lapangan</div>
        <div>Penerima di REFFO</div>
    </div>
</body>
</html>
