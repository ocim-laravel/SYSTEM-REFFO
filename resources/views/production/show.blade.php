@extends('layouts.app')

@section('title', 'Detail Produksi')

@section('content')
    <div style="max-width:1100px;margin:0 auto">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px">
            <div>
                <h1 style="margin:0 0 4px">Detail Produksi Item</h1>
                <p class="muted" style="margin:0">
                    Order: <strong>{{ $item->order?->code ?? '-' }}</strong> ·
                    Pelanggan: {{ $item->order?->customer?->name ?? '-' }} ·
                    Item: {{ $item->title }}
                </p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('production.index') }}" class="btn secondary">Kembali ke Antrean</a>
                <a href="{{ route('production.reprints.index') }}" class="btn secondary">Cetak Ulang</a>
            </div>
        </div>

        <h2>Informasi Pekerjaan</h2>
        <table>
            <tbody>
                <tr><th>Kelas Antrean</th><td>{{ $job->queue_class?->label() ?? '-' }}</td></tr>
                <tr><th>Status Job</th><td>{{ $job->status?->label() ?? '-' }}</td></tr>
                <tr><th>Status Item</th><td>{{ $item->status?->label() ?? '-' }}</td></tr>
                <tr><th>Prioritas</th><td>{{ $item->priority?->label() ?? '-' }}</td></tr>
                <tr><th>Deadline Item</th><td>{{ $item->deadline_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Qty Rencana</th><td>{{ $job->qty_planned !== null ? number_format((float) $job->qty_planned, 2, ',', '.') : '-' }}</td></tr>
                <tr><th>Qty Aktual</th><td>{{ $job->qty_actual !== null ? number_format((float) $job->qty_actual, 2, ',', '.') : '-' }}</td></tr>
                <tr><th>Mulai</th><td>{{ $job->started_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Selesai</th><td>{{ $job->finished_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><th>Penanggung Jawab</th><td>{{ $job->assignee?->name ?? '-' }}</td></tr>
                <tr><th>Dibagikan Oleh</th><td>{{ $job->assigner?->name ?? '-' }}</td></tr>
            </tbody>
        </table>

        @can('assign', $job)
            <h2>Bagikan Pekerjaan</h2>
            <form method="POST" action="{{ route('production.assign', $item) }}" style="max-width:480px">
                @csrf
                <div class="field">
                    <label for="assign_operator_id">Operator</label>
                    <select id="assign_operator_id" name="operator_id" required>
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
            @if ($job->activeOperator() === null)
                <h2>Ambil Pekerjaan</h2>
                <form method="POST" action="{{ route('production.claim', $item) }}">
                    @csrf
                    <button type="submit" class="btn">Ambil (Claim)</button>
                </form>
            @endif
        @endcan

        @can('recordProgress', $job)
            <h2>Catat Progres</h2>
            <form method="POST" action="{{ route('production.progress', $item) }}" style="max-width:560px">
                @csrf
                <div class="field">
                    <label for="qty_planned">Qty Rencana</label>
                    <input id="qty_planned" type="number" step="0.01" min="0" name="qty_planned" value="{{ old('qty_planned', $job->qty_planned) }}">
                </div>
                <div class="field">
                    <label for="qty_actual">Qty Aktual</label>
                    <input id="qty_actual" type="number" step="0.01" min="0" name="qty_actual" value="{{ old('qty_actual', $job->qty_actual) }}">
                    @error('qty_actual')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="started_at">Waktu Mulai</label>
                    <input id="started_at" type="datetime-local" name="started_at" value="{{ old('started_at', optional($job->started_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="field">
                    <label for="finished_at">Waktu Selesai</label>
                    <input id="finished_at" type="datetime-local" name="finished_at" value="{{ old('finished_at', optional($job->finished_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="field">
                    <label>
                        <input type="checkbox" name="mark_printed" value="1" @checked(old('mark_printed'))>
                        Tandai selesai/printed (wajib qty aktual tercatat)
                    </label>
                </div>
                <button type="submit" class="btn">Simpan Progres</button>
            </form>
        @endcan

        @can('handover', $job)
            <h2>Handover / Pergantian Shift</h2>
            <form method="POST" action="{{ route('production.handover', $item) }}" style="max-width:560px">
                @csrf
                <div class="field">
                    <label for="handover_operator_id">Operator Baru</label>
                    <select id="handover_operator_id" name="operator_id" required>
                        <option value="">Pilih operator</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}" @selected((string) old('operator_id') === (string) $op->id)>{{ $op->name }}</option>
                        @endforeach
                    </select>
                    @error('operator_id')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="handover_reason">Alasan Handover</label>
                    <textarea id="handover_reason" name="reason" rows="3" required>{{ old('reason') }}</textarea>
                    @error('reason')<div class="error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn">Simpan Handover</button>
            </form>
        @endcan

        @can('requestReprint', $job)
            <h2>Ajukan Cetak Ulang</h2>
            <form method="POST" action="{{ route('production.reprint.request', $item) }}" style="max-width:560px">
                @csrf
                <div class="field">
                    <label for="reprint_qty">Qty Cetak Ulang</label>
                    <input id="reprint_qty" type="number" step="0.01" min="0.01" name="qty" value="{{ old('qty') }}" required>
                    @error('qty')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="reprint_reason">Alasan</label>
                    <textarea id="reprint_reason" name="reason" rows="3" required>{{ old('reason') }}</textarea>
                    @error('reason')<div class="error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn">Ajukan Cetak Ulang</button>
            </form>
        @endcan

        <h2>Riwayat Operator / Shift</h2>
        <table>
            <thead>
                <tr>
                    <th>Operator</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Alasan Handover</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($job->operators->sortByDesc('started_at') as $record)
                    <tr>
                        <td>{{ $record->operator?->name ?? '-' }}</td>
                        <td>{{ $record->started_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $record->ended_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $record->reason ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Belum ada riwayat operator.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Pengajuan Cetak Ulang</h2>
        <table>
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>Diminta Oleh</th>
                    <th>Diproses Oleh</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($job->reprintRequests->sortByDesc('id') as $rr)
                    <tr>
                        <td>{{ number_format((float) $rr->qty, 2, ',', '.') }}</td>
                        <td>{{ $rr->reason }}</td>
                        <td>{{ $rr->status?->label() ?? '-' }}</td>
                        <td>{{ $rr->requester?->name ?? '-' }}</td>
                        <td>{{ $rr->approver?->name ?? '-' }}</td>
                        <td>{{ $rr->approved_at?->format('d/m/Y H:i') ?? $rr->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Belum ada pengajuan cetak ulang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
