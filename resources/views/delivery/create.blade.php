@extends('layouts.app')

@section('title', 'Buat Jadwal Lapangan')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="deliverySchedule()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
            <h1 style="margin:0">Buat Jadwal Pengiriman / Pemasangan</h1>
            <a href="{{ route('deliveries.index') }}" class="btn secondary">Kembali</a>
        </div>

        @if ($items->isEmpty())
            <div class="bg-white shadow rounded-lg p-4">
                <p class="muted" style="margin:0">
                    Belum ada item yang siap dijadwalkan. Item harus berstatus <strong>Sudah Cetak</strong>
                    atau <strong>Dikemas</strong> terlebih dahulu.
                </p>
            </div>
        @else
            <form method="POST" action="{{ route('deliveries.store') }}" class="bg-white shadow rounded-lg p-4">
                @csrf

                <div class="field">
                    <label for="order_id">Order</label>
                    <select id="order_id" name="order_id" x-model="orderId" x-on:change="syncItems()" required>
                        <option value="">Pilih order</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}"
                                data-completed="{{ $order->items()->whereNotIn('status', [\App\Enums\ItemStatus::Printed->value, \App\Enums\ItemStatus::Packed->value, \App\Enums\ItemStatus::Cancelled->value])->exists() ? '0' : '1' }}"
                                @selected((string) old('order_id') === (string) $order->id)>{{ $order->code }} — {{ $order->customer?->name }}</option>
                        @endforeach
                    </select>
                    @error('order_id')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="type">Jenis Pekerjaan</label>
                    <select id="type" name="type" x-model="type" x-on:change="syncItems()" required>
                        @foreach (\App\Enums\DeliveryType::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('type') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="error">{{ $message }}</div>@enderror
                    <p class="muted" style="margin:4px 0 0;font-size:12px">
                        Pemasangan hanya bisa dijadwalkan bila seluruh item order sudah selesai produksi (BR-13).
                    </p>
                </div>

                <div class="field">
                    <label>Item yang Dijadwalkan</label>
                    <div style="display:grid;gap:6px">
                        @foreach ($items as $item)
                            <label
                                class="delivery-item"
                                data-order="{{ $item->order_id }}"
                                x-show="visibleItem({{ $item->order_id }})"
                                style="display:flex;gap:8px;align-items:flex-start;border:1px solid #e5e7eb;border-radius:6px;padding:8px"
                            >
                                <input type="checkbox" name="item_ids[]" value="{{ $item->id }}"
                                    @checked(is_array(old('item_ids')) && in_array((string) $item->id, array_map('strval', old('item_ids')), true))>
                                <span>
                                    <strong>{{ $item->title }}</strong><br>
                                    <span class="muted" style="font-size:12px">
                                        {{ $item->order?->code }} · {{ $item->status?->label() }} ·
                                        Deadline {{ $item->deadline_at?->format('d/m/Y H:i') ?? '-' }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('item_ids')<div class="error">{{ $message }}</div>@enderror
                    @error('item_ids.*')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="scheduled_at">Waktu Jadwal</label>
                    <input id="scheduled_at" type="datetime-local" name="scheduled_at"
                        value="{{ old('scheduled_at') }}" required>
                    @error('scheduled_at')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field" x-show="type !== 'pickup'">
                    <label>Alamat Tujuan</label>
                    <input type="text" name="address" value="{{ old('address') }}" maxlength="500">
                    @error('address')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field" x-show="type !== 'pickup'">
                    <label>Petugas Lapangan (opsional, dapat ditugaskan kemudian)</label>
                    <div style="display:grid;gap:4px">
                        @foreach ($crews as $crew)
                            <label style="display:flex;gap:8px;align-items:center">
                                <input type="checkbox" name="crew_ids[]" value="{{ $crew->id }}"
                                    @checked(is_array(old('crew_ids')) && in_array((string) $crew->id, array_map('strval', old('crew_ids')), true))>
                                {{ $crew->name }}
                            </label>
                        @endforeach
                        @if ($crews->isEmpty())
                            <span class="muted">Belum ada pengguna dengan hak petugas lapangan.</span>
                        @endif
                    </div>
                    @error('crew_ids')<div class="error">{{ $message }}</div>@enderror
                    @error('crew_ids.*')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="notes">Catatan</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea>
                    @error('notes')<div class="error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn">Simpan Jadwal</button>
            </form>
        @endif
    </div>

    <script>
        function deliverySchedule() {
            return {
                orderId: '{{ old('order_id', '') }}',
                type: '{{ old('type', \App\Enums\DeliveryType::Delivery->value) }}',
                syncItems() {
                    document.querySelectorAll('.delivery-item').forEach((row) => {
                        if (Number(row.dataset.order) !== Number(this.orderId)) {
                            row.querySelector('input[type=checkbox]').checked = false;
                        }
                    });
                },
                visibleItem(orderId) {
                    return Number(orderId) === Number(this.orderId);
                },
            };
        }
    </script>
@endsection
