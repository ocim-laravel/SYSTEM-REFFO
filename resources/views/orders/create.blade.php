@extends('layouts.app')

@section('content')
    <h1 style="margin-top:0">Buat Order</h1>
    <p class="muted">Order buatan <strong>Designer</strong> langsung berstatus <strong>Menunggu Validasi</strong>; order buatan <strong>Admin</strong> berstatus <strong>Draft</strong> (AC-02-3, BR-03, ADR-004).</p>

    @if ($errors->any())
        <div class="alert error" style="margin-bottom:12px">
            <ul style="margin:0;padding-left:18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('orders.store') }}" id="order-form">
        @csrf

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
            <div class="field">
                <label for="customer_id">Pelanggan (aktif)</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">-- Pilih pelanggan --</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                            {{ $customer->code }} - {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="priority">Prioritas Order</label>
                <select id="priority" name="priority">
                    @foreach (App\Enums\Priority::cases() as $priority)
                        <option value="{{ $priority->value }}" @selected(old('priority', 'normal') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="deadline_at">Deadline Order</label>
                <input id="deadline_at" type="datetime-local" name="deadline_at" value="{{ old('deadline_at') }}">
            </div>
            <div class="field">
                <label for="discount">Diskon</label>
                <input id="discount" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', '0') }}">
            </div>
            <div class="field">
                <label for="delivery_fee">Biaya Kirim/Pasang (sekali per order)</label>
                <input id="delivery_fee" type="number" step="0.01" min="0" name="delivery_fee" value="{{ old('delivery_fee', '0') }}">
            </div>
        </div>

        <div class="field">
            <label for="notes">Catatan</label>
            <textarea id="notes" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
        </div>

        <h2>Item (minimal 1)</h2>
        <div id="items-container"></div>
        <p style="margin-top:8px">
            <button type="button" class="btn secondary small" id="add-item">Tambah Item</button>
        </p>

        <div style="margin-top:16px;display:flex;gap:8px">
            <button type="submit" class="btn">Simpan Order</button>
            <a href="{{ route('orders.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>

    <template id="item-template">
        <div class="card item-row" data-index="__INDEX__" style="margin-bottom:12px;border:1px solid #e5e7eb;padding:12px;border-radius:8px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <strong>Item #<span class="item-number"></span></strong>
                <button type="button" class="btn small danger remove-item">Hapus</button>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">
                <div class="field" style="margin:0">
                    <label>Judul</label>
                    <input type="text" name="items[__INDEX__][title]" required maxlength="255">
                </div>
                <div class="field" style="margin:0">
                    <label>Kategori</label>
                    <select name="items[__INDEX__][category]" required>
                        @foreach (App\Enums\ItemCategory::cases() as $category)
                            <option value="{{ $category->value }}">{{ $category->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin:0">
                    <label>Qty</label>
                    <input type="number" step="0.01" min="0.01" name="items[__INDEX__][qty]" value="1" required>
                </div>
                <div class="field" style="margin:0">
                    <label>Satuan</label>
                    <input type="text" name="items[__INDEX__][unit]" maxlength="50" placeholder="pcs, m2, lembar">
                </div>
                <div class="field" style="margin:0">
                    <label>Harga Satuan</label>
                    <input type="number" step="0.01" min="0" name="items[__INDEX__][unit_price]" value="0" required>
                </div>
                <div class="field" style="margin:0">
                    <label>Prioritas Item</label>
                    <select name="items[__INDEX__][priority]">
                        @foreach (App\Enums\Priority::cases() as $priority)
                            <option value="{{ $priority->value }}" @selected($priority->value === 'normal')>{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin:0">
                    <label>Deadline Item</label>
                    <input type="datetime-local" name="items[__INDEX__][deadline_at]">
                </div>
            </div>
        </div>
    </template>

    <script>
        (function () {
            const container = document.getElementById('items-container');
            const template = document.getElementById('item-template').innerHTML;
            let index = 0;

            function renumber() {
                const rows = container.querySelectorAll('.item-row');
                rows.forEach((row, i) => {
                    const num = row.querySelector('.item-number');
                    if (num) num.textContent = i + 1;
                });
            }

            function addItem() {
                const html = template.replaceAll('__INDEX__', String(index));
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                const row = wrap.firstChild;
                row.querySelector('.remove-item').addEventListener('click', function () {
                    if (container.querySelectorAll('.item-row').length <= 1) return;
                    row.remove();
                    renumber();
                });
                container.appendChild(row);
                index++;
                renumber();
            }

            document.getElementById('add-item').addEventListener('click', addItem);
            addItem();
        })();
    </script>
@endsection
