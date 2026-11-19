@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Pembatalan Order {{ $order->code }}</h1>
    <p class="muted">
        Pelanggan: {{ $order->customer?->name ?? '-' }} · Status saat ini: <strong>{{ $order->status->label() }}</strong>
    </p>

    <div class="card" style="max-width:760px">
        <p>
            Biaya aktual dan nilai kesepakatan dicatat <strong>terpisah</strong> (BR-15).
            Penyelesaian mengikuti kesepakatan yang dicatat, bukan otomatis membebankan seluruh biaya.
        </p>

        @if ($errors->any())
            <div class="alert error" style="margin-bottom:12px">
                <ul style="margin:0;padding-left:18px">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('orders.cancel.store', $order) }}">
            @csrf

            <div class="field">
                <label for="reason">Alasan pembatalan <span class="muted">(wajib)</span></label>
                <textarea id="reason" name="reason" rows="3" required minlength="5" maxlength="2000">{{ old('reason') }}</textarea>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                <div class="field">
                    <label for="actual_cost">Biaya aktual yang sudah dikeluarkan (Rp)</label>
                    <input id="actual_cost" type="number" step="0.01" min="0" name="actual_cost" value="{{ old('actual_cost', '0') }}" required>
                </div>
                <div class="field">
                    <label for="settlement_amount">Nilai kesepakatan penyelesaian (Rp)</label>
                    <input id="settlement_amount" type="number" step="0.01" min="0" name="settlement_amount" value="{{ old('settlement_amount', '0') }}" required>
                </div>
            </div>

            <div class="field">
                <label for="settlement_note">Catatan kesepakatan <span class="muted">(opsional)</span></label>
                <textarea id="settlement_note" name="settlement_note" rows="2" maxlength="2000">{{ old('settlement_note') }}</textarea>
            </div>

            <div style="margin-top:16px;display:flex;gap:8px">
                <button type="submit" class="btn danger" onclick="return confirm('Batalkan order ini? Tindakan ini tercatat di audit log.')">Batalkan Order</button>
                <a href="{{ route('orders.show', $order) }}" class="btn secondary">Kembali</a>
            </div>
        </form>
    </div>
@endsection
