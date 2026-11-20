@extends('layouts.app')

@section('title', 'Detail Pekerjaan Lapangan')

@section('content')
    <div style="max-width:1100px;margin:0 auto">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px">
            <div>
                <h1 style="margin:0 0 4px">Detail Pekerjaan Lapangan</h1>
                <p class="muted" style="margin:0">
                    Order: <strong>{{ $delivery->order?->code ?? '-' }}</strong> ·
                    Pelanggan: {{ $delivery->order?->customer?->name ?? '-' }} ·
                    {{ $delivery->type?->label() }}
                </p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @can('print', $delivery)
                    <a href="{{ route('deliveries.print', $delivery) }}" class="btn secondary" target="_blank" rel="noopener">Cetak Ringkasan</a>
                @endcan
                <a href="{{ route('deliveries.index') }}" class="btn secondary">Kembali</a>
            </div>
        </div>

        @if (session('status'))
            <div class="bg-green-50 text-green-800 rounded-lg p-3" style="margin-bottom:12px">{{ session('status') }}</div>
        @endif

        <h2>Informasi Jadwal</h2>
        <table>
            <tbody>
                <tr><th>Status</th><td>{{ $delivery->status?->label() ?? '-' }}</td></tr>
                <tr><th>Waktu Dijadwalkan</th><td>{{ $delivery->scheduled_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Dijadwalkan Oleh</th><td>{{ $delivery->scheduler?->name ?? '-' }}</td></tr>
                <tr><th>Alamat</th><td>{{ $delivery->address ?? '-' }}</td></tr>
                <tr><th>Petugas Lapangan</th><td>{{ $delivery->crewMembers()->pluck('name')->implode(', ') ?: '-' }}</td></tr>
                <tr><th>Waktu Berangkat</th><td>{{ $delivery->departed_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Waktu Tiba</th><td>{{ $delivery->arrived_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Selesai Oleh</th><td>{{ $delivery->completer?->name ?? '-' }}</td></tr>
                <tr><th>Waktu Selesai</th><td>{{ $delivery->completed_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Total Biaya Kirim/Pasang Order</th><td>Rp {{ number_format((float) $chargedFee, 0, ',', '.') }}</td></tr>
            </tbody>
        </table>

        <h2>Item yang Dikerjakan</h2>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Status Item</th>
                    <th>Deadline</th>
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

        @if ($delivery->notes)
            <h2>Catatan</h2>
            <p style="white-space:pre-line">{{ $delivery->notes }}</p>
        @endif

        @can('assignCrew', $delivery)
            @if ($delivery->type !== \App\Enums\DeliveryType::Pickup && $delivery->status !== \App\Enums\DeliveryStatus::Completed)
                <h2>Tugaskan Petugas Lapangan</h2>
                <form method="POST" action="{{ route('deliveries.crew', $delivery) }}" style="max-width:480px">
                    @csrf
                    <div class="field">
                        <label>Petugas</label>
                        <div style="display:grid;gap:4px">
                            @foreach ($crews as $crew)
                                <label style="display:flex;gap:8px;align-items:center">
                                    <input type="checkbox" name="crew_ids[]" value="{{ $crew->id }}"
                                        @checked(in_array($crew->id, $delivery->crew ?? [], true))>
                                    {{ $crew->name }}
                                </label>
                            @endforeach
                            @if ($crews->isEmpty())
                                <span class="muted">Belum ada petugas lapangan tersedia.</span>
                            @endif
                        </div>
                        @error('crew_ids')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn">Simpan Petugas</button>
                </form>
            @endif
        @endcan

        @can('recordProgress', $delivery)
            @if ($delivery->status !== \App\Enums\DeliveryStatus::Completed)
                <h2>Catat Progres</h2>
                <form method="POST" action="{{ route('deliveries.progress', $delivery) }}" style="max-width:520px">
                    @csrf
                    <div class="field">
                        <label for="progress_stage">Tahap</label>
                        <select id="progress_stage" name="stage" required>
                            @foreach (\App\Enums\DeliveryProgressStage::cases() as $case)
                                <option value="{{ $case->value }}" @selected(old('stage') === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        @error('stage')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="progress_note">Catatan</label>
                        <textarea id="progress_note" name="note" rows="2" maxlength="500">{{ old('note') }}</textarea>
                        @error('note')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn">Simpan Progres</button>
                </form>
            @endif
        @endcan

        @can('attachProof', $delivery)
            @if ($delivery->status !== \App\Enums\DeliveryStatus::Completed)
                <h2>Unggah Foto Bukti</h2>
                <form method="POST" action="{{ route('deliveries.proof', $delivery) }}" enctype="multipart/form-data" style="max-width:520px">
                    @csrf
                    <div class="field">
                        <label for="photo">Foto Bukti (JPG/PNG, maks. 10 MB)</label>
                        <input id="photo" type="file" name="photo" accept="image/jpeg,image/png" required>
                        @error('photo')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="proof_note">Catatan Bukti</label>
                        <textarea id="proof_note" name="note" rows="2" maxlength="500">{{ old('note') }}</textarea>
                        @error('note')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn">Unggah & Konfirmasi</button>
                </form>
                <p class="muted" style="font-size:12px">
                    Bukti berupa foto dan konfirmasi petugas REFFO. Tidak memerlukan tanda tangan pelanggan.
                </p>
            @endif
        @endcan

        <h2>Bukti Penyelesaian</h2>
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Catatan</th>
                    <th>Diunggah Oleh</th>
                    <th>Dikonfirmasi Oleh</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($delivery->proofs as $proof)
                    <tr>
                        <td>
                            @can('downloadProof', $proof)
                                <a href="{{ route('deliveries.proof.download', $proof) }}">Lihat / Unduh</a>
                            @else
                                Tersimpan
                            @endcan
                        </td>
                        <td>{{ $proof->note ?? '-' }}</td>
                        <td>{{ $proof->uploader?->name ?? '-' }}</td>
                        <td>{{ $proof->confirmer?->name ?? '-' }}</td>
                        <td>{{ $proof->uploaded_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada bukti diunggah.</td></tr>
                @endforelse
            </tbody>
        </table>

        @can('complete', $delivery)
            @if ($delivery->status !== \App\Enums\DeliveryStatus::Completed)
                <h2>Selesaikan Pekerjaan</h2>
                <form method="POST" action="{{ route('deliveries.complete', $delivery) }}">
                    @csrf
                    <button type="submit" class="btn">Tandai Selesai</button>
                </form>
                <p class="muted" style="font-size:12px">
                    Pengiriman/pemasangan wajib memiliki minimal satu foto bukti dan petugas lapangan.
                    Ambil sendiri tidak memerlukan keduanya.
                </p>
            @endif
        @endcan
    </div>
@endsection
