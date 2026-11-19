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
                <a href="{{ route('customers.create') }}" target="_blank" class="btn secondary small" style="margin-top:8px">Tambah Pelanggan</a>
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
        </div>

        <div class="field">
            <label>Jenis Layanan</label>
            <label>
                <input type="checkbox" name="services[printing]" value="1" @checked(old('services.printing', true))>Printing
            </label>
            <label>
                <input type="checkbox" name="services[kirim]" value="1" @checked(old('services.kirim'))>Kirim
            </label>
            <label>
                <input type="checkbox" name="services[reklame]" value="1" @checked(old('services.reklame'))>Reklame
            </label>
        </div>

        <div class="field" id="delivery_address_field" style="display:none">
            <label for="delivery_address">Alamat Pengiriman/Pemasangan</label>
            <textarea id="delivery_address" name="delivery_address" rows="2" maxlength="1000" disabled>{{ old('delivery_address') }}</textarea>
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

        <h2>Ringkasan</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
            <div class="field">
                <label>Subtotal</label>
                <div id="order_subtotal">Rp 0</div>
            </div>
            <div class="field">
                <label for="discount">Diskon</label>
                <input id="discount" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', '0') }}">
            </div>
            <div class="field">
                <label for="delivery_fee">Biaya Kirim/Pasang (sekali per order)</label>
                <input id="delivery_fee" type="number" step="0.01" min="0" name="delivery_fee" value="{{ old('delivery_fee', '0') }}">
            </div>
            <div class="field">
                <label>Total</label>
                <div id="order_total"><strong>Rp 0</strong></div>
            </div>
        </div>

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
                        <option value="printing">Printing</option>
                        <option value="reklame">Reklame</option>
                    </select>
                </div>
                <div class="field" style="margin:0">
                    <label>Qty</label>
                    <input type="number" step="0.01" min="0.01" name="items[__INDEX__][qty]" value="1" required>
                </div>
                <div class="field" style="margin:0">
                    <label>Satuan</label>
                    <input type="text" name="items[__INDEX__][unit]" maxlength="50" placeholder="pcs, m2, box">
                </div>
                <div class="field" style="margin:0">
                    <label>Harga Satuan</label>
                    <input type="number" step="0.01" min="0" name="items[__INDEX__][unit_price]" value="0" required>
                </div>
                <div class="field" style="margin:0">
                    <label>Prioritas Item</label>
                    <select name="items[__INDEX__][priority]">
                        <option value="normal">Normal</option>
                        <option value="h1">H-1</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="field" style="margin:0">
                    <label>Deadline Item</label>
                    <input type="datetime-local" name="items[__INDEX__][deadline_at]">
                </div>
            </div>

            <div class="item-mode-printing" style="display:none;margin-top:10px">
                <label>Sub-tipe Produk (kategori Printing)</label>
                <label>
                    <input type="radio" name="items[__INDEX__][specification][mode]" value="pcs" checked> Produk (pcs)
                </label>
                <label>
                    <input type="radio" name="items[__INDEX__][specification][mode]" value="m2"> Luas (m²)
                </label>

                <div data-mode-box="pcs" style="margin-top:8px">
                    <p class="muted" style="margin:0">Qty = jumlah produk (pcs/satuan).</p>
                </div>

                <div data-mode-box="m2" style="display:none;margin-top:8px">
                    <div class="field" style="margin:0">
                        <label>Bahan</label>
                        <input type="text" name="items[__INDEX__][specification][bahan]" maxlength="255">
                    </div>
                    <div class="field" style="margin:0">
                        <label>Ukuran Panjang (m)</label>
                        <input type="number" step="0.01" min="0" name="items[__INDEX__][specification][ukuran_panjang]">
                    </div>
                    <div class="field" style="margin:0">
                        <label>Ukuran Lebar (m)</label>
                        <input type="number" step="0.01" min="0" name="items[__INDEX__][specification][ukuran_lebar]">
                    </div>
                    <div class="field" style="margin:0">
                        <label>Quantity</label>
                        <input type="number" step="0.01" min="0.01" name="items[__INDEX__][specification][quantity_asli]" value="1" required>
                    </div>
                    <div class="field" style="margin:0">
                        <label>Jenis Finishing</label>
                        <select name="items[__INDEX__][specification][jenis_finishing]">
                            <option value="">-- Pilih --</option>
                            <option value="laminasi">Laminasi</option>
                            <option value="potong">Potong</option>
                            <option value="lipat">Lipat</option>
                            <option value="jahit">Jahit</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="item-actions" style="margin-top:10px">
                <label>
                    <input type="checkbox" name="reklame_actions[]" value="konstruksi_rangka" disabled> Konstruksi/Rangka
                </label>
                <label>
                    <input type="checkbox" name="reklame_actions[]" value="pemasangan" disabled> Pemasangan
                </label>
            </div>
        </div>
    </template>

    <script>
        (function () {
            const container = document.getElementById('items-container');
            const template = document.getElementById('item-template').innerHTML;
            const kirimCheckbox = document.querySelector('input[name="services[kirim]"]');
            const reklameCheckbox = document.querySelector('input[name="services[reklame]"]');
            const deliveryAddressField = document.getElementById('delivery_address_field');
            const deliveryAddressInput = document.getElementById('delivery_address');
            let index = 0;

            function renumber() {
                container.querySelectorAll('.item-row').forEach(function (row, i) {
                    const num = row.querySelector('.item-number');
                    if (num) {
                        num.textContent = i + 1;
                    }
                });
            }

            function addItem() {
                const html = template.replaceAll('__INDEX__', String(index));
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                const row = wrap.firstChild;
                row.dataset.index = String(index);
                attachRowHandlers(row);
                container.appendChild(row);
                index++;
                renumber();
            }

            function itemInput(row, name) {
                return row.querySelector('input[name="items[' + row.dataset.index + '][' + name + '"]');
            }

            function itemSelect(row, name) {
                return row.querySelector('select[name="items[' + row.dataset.index + '][' + name + '"]');
            }

            function itemRadio(row, value) {
                const input = row.querySelector('input[name="items[' + row.dataset.index + '][specification][mode]"]');
                if (input === null) {
                    return null;
                }

                if (value === undefined) {
                    return input;
                }

                return input.value === value ? input : null;
            }

            function refreshItemRowUI(row) {
                const category = itemSelect(row, 'category')?.value;
                const modeM2 = itemRadio(row, 'm2');
                const boxPcs = row.querySelector('[data-mode-box="pcs"]');
                const boxM2 = row.querySelector('[data-mode-box="m2"]');
                const isPrinting = category === 'printing';
                const isModeM2 = modeM2 !== null;

                if (boxPcs) {
                    boxPcs.style.display = isPrinting && ! isModeM2 ? '' : 'none';
                }

                if (boxM2) {
                    boxM2.style.display = isPrinting && isModeM2 ? '' : 'none';
                }
            }

            function attachRowHandlers(row) {
                const categorySelect = itemSelect(row, 'category');
                const modeRadios = row.querySelectorAll('input[name="items[' + row.dataset.index + '][specification][mode]"]');

                categorySelect.addEventListener('change', function () {
                    refreshItemRowUI(row);
                });

                modeRadios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        refreshItemRowUI(row);

                        if (radio.value === 'm2') {
                            const unitInput = itemInput(row, 'unit');

                            if (unitInput) {
                                unitInput.value = 'm2';
                            }
                        } else {
                            const unitInput = itemInput(row, 'unit');
                            const quantityInput = row.querySelector('input[name="items[' + row.dataset.index + '][specification][quantity_asli]"]');

                            if (unitInput) {
                                unitInput.value = 'pcs';
                            }

                            if (quantityInput) {
                                quantityInput.value = itemInput(row, 'qty')?.value ?? quantityInput.value;
                            }
                        }
                    });
                });

                row.querySelector('.remove-item').addEventListener('click', function () {
                    if (container.querySelectorAll('.item-row').length <= 1) {
                        return;
                    }

                    row.remove();
                    renumber();
                });
            }

            function formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(value || 0);
            }

            function computeSubtotal() {
                let subtotal = 0;

                container.querySelectorAll('.item-row').forEach(function (row) {
                    const qty = parseFloat(itemInput(row, 'qty')?.value) || 0;
                    const unitPrice = parseFloat(itemInput(row, 'unit_price')?.value) || 0;
                    let total = qty * unitPrice;
                    const modeM2 = itemRadio(row, 'm2');

                    if (modeM2) {
                        const panjang = parseFloat(row.querySelector('input[name="items[' + row.dataset.index + '][specification][ukuran_panjang]"]')?.value) || 0;
                        const lebar = parseFloat(row.querySelector('input[name="items[' + row.dataset.index + '][specification][ukuran_lebar]"]')?.value) || 0;
                        const luas = panjang * lebar * qty;
                        total = luas * unitPrice;
                    }

                    subtotal += total;
                });

                return subtotal;
            }

            function updatePreview() {
                const subtotal = computeSubtotal();
                const discount = parseFloat(document.getElementById('discount')?.value) || 0;
                const deliveryFee = parseFloat(document.getElementById('delivery_fee')?.value) || 0;
                const total = subtotal - discount + deliveryFee;

                if (document.getElementById('order_subtotal')) {
                    document.getElementById('order_subtotal').textContent = formatRupiah(subtotal);
                }

                if (document.getElementById('order_total')) {
                    document.getElementById('order_total').innerHTML = '<strong>' + formatRupiah(total) + '</strong>';
                }
            }

            container.addEventListener('input', function (event) {
                if (event.target instanceof Element) {
                    updatePreview();
                }
            });

            container.addEventListener('change', function (event) {
                if (event.target instanceof Element) {
                    updatePreview();
                }
            });

            document.getElementById('discount')?.addEventListener('input', updatePreview);
            document.getElementById('delivery_fee')?.addEventListener('input', updatePreview);

            if (kirimCheckbox) {
                kirimCheckbox.addEventListener('change', function () {
                    if (kirimCheckbox.checked) {
                        deliveryAddressField.style.display = '';
                        deliveryAddressInput.disabled = false;
                    } else {
                        deliveryAddressField.style.display = 'none';
                        deliveryAddressInput.disabled = true;
                    }
                });
            }

            if (reklameCheckbox) {
                reklameCheckbox.addEventListener('change', function () {
                    container.querySelectorAll('.item-actions').forEach(function (group) {
                        group.querySelectorAll('label').forEach(function (label) {
                            label.style.display = reklameCheckbox.checked ? '' : 'none';
                        });

                        group.querySelectorAll('input').forEach(function (input) {
                            input.disabled = ! reklameCheckbox.checked;
                        });
                    });
                });
            }

            document.getElementById('add-item').addEventListener('click', addItem);
            addItem();

            updatePreview();
        })();
    </script>
@endsection
