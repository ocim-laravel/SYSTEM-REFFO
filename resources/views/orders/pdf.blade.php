<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Order {{ $order->code }}</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #222;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 4px;
        }

        h2 {
            font-size: 12px;
            margin: 16px 0 6px;
            border-bottom: 1px solid #999;
            padding-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #bbb;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #eeeeee;
        }

        .label {
            width: 150px;
        }

        .right {
            text-align: right;
        }

        .muted {
            color: #666666;
        }
    </style>
</head>
<body>
    <h1>Ringkasan Order {{ $order->code }}</h1>
    <p class="muted">Dicetak dari sistem REFFO pada {{ now()->format('d/m/Y H:i') }} WIB</p>

    <h2>Pelanggan</h2>
    <table>
        <tbody>
            <tr><th class="label">Kode</th><td>{{ $order->customer?->code ?? '-' }}</td></tr>
            <tr><th class="label">Nama</th><td>{{ $order->customer?->name ?? '-' }}</td></tr>
            <tr><th class="label">Tipe</th><td>{{ $order->customer?->type?->label() ?? '-' }}</td></tr>
            <tr><th class="label">Telepon</th><td>{{ $order->customer?->phone ?? '-' }}</td></tr>
            <tr><th class="label">Alamat</th><td>{{ $order->customer?->address ?? '-' }}</td></tr>
        </tbody>
    </table>

    <h2>Item</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Spesifikasi</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th>Total</th>
                <th>Deadline</th>
                <th>Prioritas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                @php
                    $itemSpec = $item->specification;
                    $specMode = is_array($itemSpec) ? (string) ($itemSpec['mode'] ?? '') : '';
                    $specTindakan = is_array($itemSpec) ? ($itemSpec['tindakan'] ?? null) : null;
                    $tindakanLabels = [];
                    foreach ((array) $specTindakan as $action) {
                        $action = trim((string) $action);

                        if ($action === '') {
                            continue;
                        }

                        $tindakanLabels[] = App\Enums\ActionType::from($action)->label();
                    }
                    $specText = '-';
                    if ($specMode === 'm2') {
                        $specText = 'Ukuran: '.(float) ($itemSpec['ukuran_panjang'] ?? 0).' m × '.(float) ($itemSpec['ukuran_lebar'] ?? 0).' m, Jumlah: '.(float) ($itemSpec['quantity_asli'] ?? 0).' lembar';
                    } elseif ($specMode === 'pcs') {
                        $specText = 'Paket Produk Jadi';
                    }

                    if ($tindakanLabels !== []) {
                        $specText .= ', Tindakan: '.implode(', ', $tindakanLabels);
                    }

                    $luasTagihan = (string) ((float) ($itemSpec['luas_m2'] ?? 0));
                @endphp
                <tr>
                    <td>{{ $item->seq }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->category->label() }}</td>
                    <td>{{ $specText }}</td>
                    <td class="right">
                        @if ($specMode === 'm2')
                            {{ rtrim(rtrim(number_format((float) $luasTagihan, 2, ',', '.'), '0'), ',') }}
                        @else
                            {{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}
                        @endif
                    </td>
                    <td>
                        @if ($specMode === 'm2')
                            m²
                        @elseif ($specMode === 'pcs')
                            pcs
                        @else
                            {{ $item->unit === 'm2' ? 'm²' : ($item->unit ?? '-') }}
                        @endif
                    </td>
                    <td class="right">Rp {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((float) $item->total, 2, ',', '.') }}</td>
                    <td>{{ $item->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $item->priority->label() }}</td>
                    <td>{{ $item->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="muted">Belum ada item.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Ringkasan</h2>
    <table>
        <tbody>
            <tr><th class="label">Subtotal</th><td class="right">Rp {{ number_format((float) $order->subtotal, 2, ',', '.') }}</td></tr>
            <tr><th class="label">Diskon</th><td class="right">Rp {{ number_format((float) $order->discount, 2, ',', '.') }}</td></tr>
            <tr><th class="label">Biaya Kirim/Pasang</th><td class="right">Rp {{ number_format((float) $order->delivery_fee, 2, ',', '.') }} (sekali per order)</td></tr>
            <tr><th class="label">Total</th><td class="right"><strong>Rp {{ number_format((float) $order->total, 2, ',', '.') }}</strong></td></tr>
            <tr><th class="label">Status Order</th><td>{{ $order->status->label() }}</td></tr>
            <tr><th class="label">Deadline Order</th><td>{{ $order->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
            <tr><th class="label">Catatan</th><td>{{ $order->notes ?: '-' }}</td></tr>
        </tbody>
    </table>
</body>
</html>
