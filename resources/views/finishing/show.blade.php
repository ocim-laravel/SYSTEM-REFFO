@extends('layouts.app')

@section('title', 'Detail Finishing')

@section('content')
    <div style="max-width:1100px;margin:0 auto">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px">
            <div>
                <h1 style="margin:0 0 4px">Detail Finishing / QC</h1>
                <p class="muted" style="margin:0">
                    Order: <strong>{{ $item->order?->code ?? '-' }}</strong> ·
                    Pelanggan: {{ $item->order?->customer?->name ?? '-' }} ·
                    Item: {{ $item->title }}
                </p>
            </div>
            <a href="{{ route('finishing.index') }}" class="btn secondary">Kembali ke Antrean</a>
        </div>

        <h2>Informasi Pekerjaan</h2>
        <table>
            <tbody>
                <tr><th>Status Item</th><td>{{ $item->status?->label() ?? '-' }}</td></tr>
                <tr><th>Prioritas</th><td>{{ $item->priority?->label() ?? '-' }}</td></tr>
                <tr><th>Deadline Item</th><td>{{ $item->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Status Finishing</th><td>{{ $job?->status?->label() ?? 'Belum ada pekerjaan' }}</td></tr>
                <tr><th>Jenis Finishing</th><td>{{ $job?->type?->label() ?? '-' }}</td></tr>
                <tr><th>Penanggung Jawab</th><td>{{ $job?->assignee?->name ?? '-' }}</td></tr>
                <tr><th>Dibagikan Oleh</th><td>{{ $job?->assigner?->name ?? '-' }}</td></tr>
                <tr><th>Catatan Progres</th><td>{{ $job?->progress_note ?? '-' }}</td></tr>
            </tbody>
        </table>

        @if ($job === null && $item->status === \App\Enums\ItemStatus::Printed)
            <h2>Masukkan ke Antrean Finishing</h2>
            <form method="POST" action="{{ route('finishing.intake', $item) }}">
                @csrf
                <button type="submit" class="btn">Tambah Antrean Finishing</button>
            </form>
        @endif

        @if ($job)
            @can('assign', $job)
                <h2>Bagikan Pekerjaan</h2>
                <form method="POST" action="{{ route('finishing.assign', $item) }}" style="max-width:480px">
                    @csrf
                    <div class="field">
                        <label for="finishing_operator_id">Operator Finishing</label>
                        <select id="finishing_operator_id" name="operator_id" required>
                            <option value="">Pilih operator</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}" @selected((string) old('operator_id') === (string) $op->id)>{{ $op->name }}</option>
                            @endforeach
                        </select>
                        @error('operator_id')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn">Bagikan</button>
                </form>
            @endcan

            @can('claim', $job)
                @if ($job->assigned_to === null)
                    <h2>Ambil Pekerjaan</h2>
                    <form method="POST" action="{{ route('finishing.claim', $item) }}">
                        @csrf
                        <button type="submit" class="btn">Ambil (Claim)</button>
                    </form>
                @endif
            @endcan

            @can('recordProgress', $job)
                <h2>Catat Progres Finishing</h2>
                <form method="POST" action="{{ route('finishing.progress', $item) }}" style="max-width:560px">
                    @csrf
                    <div class="field">
                        <label for="finishing_started_at">Waktu Mulai</label>
                        <input id="finishing_started_at" type="datetime-local" name="started_at" value="{{ old('started_at', optional($job->started_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="field">
                        <label for="finishing_finished_at">Waktu Selesai</label>
                        <input id="finishing_finished_at" type="datetime-local" name="finished_at" value="{{ old('finished_at', optional($job->finished_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="field">
                        <label for="finishing_progress_note">Catatan Progres</label>
                        <textarea id="finishing_progress_note" name="progress_note" rows="3">{{ old('progress_note', $job->progress_note) }}</textarea>
                        @error('progress_note')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn">Simpan Progres</button>
                </form>
            @endcan

            @can('recordQc', $job)
                <h2>Isi Hasil QC</h2>
                <form method="POST" action="{{ route('finishing.qc', $item) }}" style="max-width:560px">
                    @csrf
                    <div class="field">
                        <label for="qc_result">Hasil</label>
                        <select id="qc_result" name="result" required>
                            <option value="pass" @selected(old('result') === 'pass')>Lulus</option>
                            <option value="fail" @selected(old('result') === 'fail')>Gagal</option>
                        </select>
                        @error('result')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="qc_notes">Catatan (wajib bila gagal)</label>
                        <textarea id="qc_notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="qc_redo_stage">Tahap Pengulangan (bila gagal)</label>
                        <select id="qc_redo_stage" name="redo_stage">
                            <option value="">Pilih tahap</option>
                            <option value="finishing" @selected(old('redo_stage') === 'finishing')>Finishing</option>
                            <option value="cetak" @selected(old('redo_stage') === 'cetak')>Cetak</option>
                        </select>
                        @error('redo_stage')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn">Simpan Hasil QC</button>
                </form>
            @endcan
        @endif

        <h2>Rework</h2>
        <table>
            <thead>
                <tr>
                    <th>Alasan</th>
                    <th>Tahap</th>
                    <th>Status</th>
                    <th>Dipelaku Oleh</th>
                    <th>Selesai Oleh</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reworks as $rework)
                    <tr>
                        <td>{{ $rework->reason }}</td>
                        <td>{{ $rework->redo_stage?->label() ?? '-' }}</td>
                        <td>{{ $rework->status?->label() ?? '-' }}</td>
                        <td>{{ $rework->qcRecord?->checker?->name ?? '-' }}</td>
                        <td>{{ $rework->doneBy?->name ?? '-' }}</td>
                        <td>
                            @can('manageRework', $rework)
                                @if ($rework->status !== \App\Enums\ReworkStatus::Done)
                                    <form method="POST" action="{{ route('finishing.rework.start', [$item, $rework]) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" class="btn small secondary">Mulai</button>
                                    </form>
                                    <form method="POST" action="{{ route('finishing.rework.complete', [$item, $rework]) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" class="btn small">Selesaikan</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Belum ada rework.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($job)
            @can('pack', $job)
                @if ($packings->isEmpty())
                    <h2>Mulai Packing</h2>
                    <form method="POST" action="{{ route('finishing.packing.start', $item) }}">
                        @csrf
                        <button type="submit" class="btn">Mulai Packing</button>
                    </form>
                @endif
            @endcan
        @endif

        <h2>Riwayat Packing</h2>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Dipelaku Oleh</th>
                    <th>Waktu</th>
                    <th>Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($packings as $packing)
                    <tr>
                        <td>{{ $packing->status?->label() ?? '-' }}</td>
                        <td>{{ $packing->packer?->name ?? '-' }}</td>
                        <td>{{ $packing->packed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $packing->note ?? '-' }}</td>
                        <td>
                            @if ($job && $packing->status !== \App\Enums\PackingStatus::Packed)
                                @can('pack', $job)
                                    <form method="POST" action="{{ route('finishing.packing.complete', [$item, $packing]) }}" style="max-width:420px">
                                        @csrf
                                        <div class="field">
                                            <label for="packing_note_{{ $packing->id }}">Catatan Packing</label>
                                            <textarea id="packing_note_{{ $packing->id }}" name="note" rows="2">{{ old('note') }}</textarea>
                                            @error('note')<div class="error">{{ $message }}</div>@enderror
                                        </div>
                                        <button type="submit" class="btn small">Selesaikan Packing</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada packing.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Riwayat Finishing</h2>
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Tahap</th>
                    <th>Keterangan</th>
                    <th>Pelaku</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $event)
                    <tr>
                        <td>{{ $event['at']?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $event['phase'] }}</td>
                        <td>{{ $event['label'] }}</td>
                        <td>{{ $event['actor'] }}</td>
                        <td>{{ $event['note'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada riwayat finishing.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
