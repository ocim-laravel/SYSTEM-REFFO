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

        <div id="order-entry-step">

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
                        <option value="{{ $priority->value }}" data-label="{{ $priority->label() }}" @selected(old('priority', 'normal') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="deadline_at">Deadline Order</label>
                <input id="deadline_at" type="datetime-local" name="deadline_at" value="{{ old('deadline_at') }}">
            </div>
        </div>

        <fieldset class="field" style="border:0;padding:0">
            <legend style="font-weight:600;margin-bottom:6px">Jenis Layanan</legend>
            <label>
                <input type="checkbox" name="services[printing]" value="1" @checked(old('services') === null ? true : (bool) old('services.printing'))>Printing
            </label>
            <label>
                <input type="checkbox" name="services[kirim]" value="1" @checked(old('services.kirim'))>Kirim
            </label>
            <label>
                <input type="checkbox" name="services[reklame]" value="1" @checked(old('services.reklame'))>Reklame
            </label>
        </fieldset>

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

        <div style="margin-top:16px;display:flex;gap:8px">
            <button type="button" class="btn" id="btn-lanjut-ke-ringkasan">Lanjut ke Ringkasan</button>
            <a href="{{ route('orders.index') }}" class="btn secondary">Batal</a>
        </div>
    </div>

    <div id="order-summary-step" style="display:none">
        <h2>Ringkasan Order</h2>
        <p class="muted">Ringkasan sebelum order diproses. Subtotal dihitung otomatis dari item; diskon dan biaya kirim dapat diedit. Backend tetap sumber kebenaran total.</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
            <div class="field">
                <label>Data Pelanggan</label>
                <div><strong id="summary-customer-name">-</strong></div>
            </div>
            <div class="field">
                <label>Prioritas</label>
                <div id="summary-priority">-</div>
            </div>
            <div class="field">
                <label>Deadline</label>
                <div id="summary-deadline">-</div>
            </div>
            <div class="field">
                <label>Jenis Layanan</label>
                <div id="summary-services">-</div>
            </div>
        </div>

        <h3>Item</h3>
        <table id="order-summary-items" style="width:100%;border-collapse:collapse">
            <thead>
                <tr>
                    <th style="text-align:left">#</th>
                    <th style="text-align:left">Judul</th>
                    <th style="text-align:left">Jenis</th>
                    <th style="text-align:left">Spesifikasi</th>
                    <th style="text-align:right">Qty</th>
                    <th style="text-align:left">Satuan</th>
                    <th style="text-align:right">Harga Satuan</th>
                    <th style="text-align:right">Total</th>
                </tr>
            </thead>
            <tbody id="summary-items-tbody"></tbody>
        </table>

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
            <button type="button" class="btn secondary" id="btn-back-edit">Kembali Edit</button>
            <button type="submit" class="btn" id="btn-proses-order">Proses Order</button>
        </div>
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
                    <label>Jenis Item</label>
                    <select name="items[__INDEX__][item_type]" required>
                        <option value="printing">Printing (m²)</option>
                        <option value="produk">Produk/Produk Jadi (pcs)</option>
                    </select>
                </div>
                <input type="hidden" name="items[__INDEX__][category]" value="printing">
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

            <div class="item-mode-box" style="margin-top:10px">
                <label>Metode Hitung Item</label>
                <label>
                    <input type="radio" name="items[__INDEX__][specification][mode]" value="m2" checked> Luas (m²)
                </label>
                <label>
                    <input type="radio" name="items[__INDEX__][specification][mode]" value="pcs"> Produk (pcs)
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
                    <div style="margin-top:8px">
                        Luas: <span class="luas-preview">0</span> m²
                    </div>
                </div>
            </div>

            <div class="item-actions" style="margin-top:10px">
                <label>
                    <input type="checkbox" name="items[__INDEX__][specification][tindakan][]" value="konstruksi_rangka" disabled> Konstruksi/Rangka
                </label>
                <label>
                    <input type="checkbox" name="items[__INDEX__][specification][tindakan][]" value="pemasangan" disabled> Pemasangan
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
            const initialItems = @json(old('items', []));
            let index = 0;

            const entryStep = document.getElementById('order-entry-step');
            const summaryStep = document.getElementById('order-summary-step');
            const btnLanjutKeRingkasan = document.getElementById('btn-lanjut-ke-ringkasan');
            const btnBackEdit = document.getElementById('btn-back-edit');
            const customerSelect = document.getElementById('customer_id');
            const prioritySelect = document.getElementById('priority');
            const deadlineInput = document.getElementById('deadline_at');

            function renumber() {
                container.querySelectorAll('.item-row').forEach(function (row, i) {
                    const num = row.querySelector('.item-number');
                    if (num) {
                        num.textContent = i + 1;
                    }
                });
            }

            function addItem(initialData) {
                initialData = initialData || {};
                const html = template.replaceAll('__INDEX__', String(index));
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                const row = wrap.firstChild;
                row.dataset.index = String(index);
                attachRowHandlers(row);
                container.appendChild(row);

                const specification = initialData.specification || {};
                const setItemField = function (field, value) {
                    const selector = 'input[name="items[' + index + '][' + field + '"], select[name="items[' + index + '][' + field + '"]';
                    const element = row.querySelector(selector);
                    if (element && value !== undefined && value !== null) {
                        element.value = value;
                    }
                };

                setItemField('title', initialData.title);
                setItemField('item_type', initialData.item_type);
                setItemField('category', initialData.category);
                setItemField('qty', initialData.qty);
                setItemField('unit', initialData.unit);
                setItemField('unit_price', initialData.unit_price);
                setItemField('priority', initialData.priority);
                setItemField('deadline_at', initialData.deadline_at);
                setItemField('specification][bahan', specification.bahan);
                setItemField('specification][ukuran_panjang', specification.ukuran_panjang);
                setItemField('specification][ukuran_lebar', specification.ukuran_lebar);
                setItemField('specification][quantity_asli', specification.quantity_asli);
                setItemField('specification][jenis_finishing', specification.jenis_finishing);

                const mode = specification.mode === 'm2' ? 'm2' : 'pcs';
                const m2Radio = itemRadio(row, 'm2');
                const pcsRadio = itemRadio(row, 'pcs');
                if (m2Radio) {
                    m2Radio.checked = mode === 'm2';
                }
                if (pcsRadio) {
                    pcsRadio.checked = mode === 'pcs';
                }

                const actions = Array.isArray(specification.tindakan) ? specification.tindakan : [];
                row.querySelectorAll('.item-actions input[type="checkbox"]').forEach(function (action) {
                    action.checked = actions.indexOf(action.value) !== -1;
                });

                refreshItemRowUI(row);
                index++;
                renumber();
            }

            function itemInput(row, name) {
                return row.querySelector('input[name="items[' + row.dataset.index + '][' + name + ']"]');
            }

            function itemSelect(row, name) {
                return row.querySelector('select[name="items[' + row.dataset.index + '][' + name + ']"]');
            }

            function itemRadio(row, value) {
                const modeFieldName = 'items[' + row.dataset.index + '][specification][mode]';
                return row.querySelector('input[name="' + modeFieldName + '"][value="' + value + '"]');
            }

            function selectedMode(row) {
                const modeFieldName = 'items[' + row.dataset.index + '][specification][mode]';
                const selected = row.querySelector('input[name="' + modeFieldName + '"]:checked]');
                return selected ? selected.value : 'pcs';
            }

            function setFieldState(field, enabled, required) {
                if (!field) {
                    return;
                }

                field.disabled = ! enabled;
                field.required = Boolean(required && enabled);
            }

            function calcLuas(row) {
                const panjang = parseFloat(row.querySelector('input[name="items[' + row.dataset.index + '][specification][ukuran_panjang]"]')?.value) || 0;
                const lebar = parseFloat(row.querySelector('input[name="items[' + row.dataset.index + '][specification][ukuran_lebar]"]')?.value) || 0;
                const quantity = parseFloat(row.querySelector('input[name="items[' + row.dataset.index + '][specification][quantity_asli]"]')?.value) || 0;
                return panjang * lebar * quantity;
            }

            function refreshItemRowUI(row) {
                const category = itemSelect(row, 'item_type')?.value || 'printing';
                const mode = selectedMode(row);
                const boxPcs = row.querySelector('[data-mode-box="pcs"]');
                const boxM2 = row.querySelector('[data-mode-box="m2"]');
                const isPrinting = category === 'printing';
                const isModeM2 = isPrinting && mode === 'm2';
                const quantityInput = row.querySelector('input[name="items[' + row.dataset.index + '][specification][quantity_asli]"]');
                const lengthInput = row.querySelector('input[name="items[' + row.dataset.index + '][specification][ukuran_panjang]"]');
                const widthInput = row.querySelector('input[name="items[' + row.dataset.index + '][specification][ukuran_lebar]"]');
                const unitInput = itemInput(row, 'unit');
                const qtyInput = itemInput(row, 'qty');
                const modeRadios = row.querySelectorAll('input[name="items[' + row.dataset.index + '][specification][mode]"]');

                if (boxPcs) {
                    boxPcs.style.display = mode === 'pcs' ? '' : 'none';
                }

                if (boxM2) {
                    boxM2.style.display = isModeM2 ? '' : 'none';
                }

                modeRadios.forEach(function (radio) {
                    radio.disabled = isPrinting ? radio.value === 'pcs' : radio.value === 'm2';
                });

                if (isModeM2) {
                    setFieldState(quantityInput, true, true);
                    setFieldState(lengthInput, true, true);
                    setFieldState(widthInput, true, true);
                    if (unitInput) {
                        unitInput.value = 'm2';
                    }
                    const luas = calcLuas(row);
                    if (qtyInput) {
                        qtyInput.value = luas > 0 ? String(luas) : (qtyInput.value || '1');
                    }
                } else {
                    setFieldState(quantityInput, false, false);
                    setFieldState(lengthInput, false, false);
                    setFieldState(widthInput, false, false);
                    if (unitInput) {
                        unitInput.value = 'pcs';
                    }
                    if (qtyInput) {
                        qtyInput.disabled = false;
                        qtyInput.required = true;
                    }
                }
            }

            function attachRowHandlers(row) {
                const categorySelect = itemSelect(row, 'item_type');
                const modeRadios = row.querySelectorAll('input[name="items[' + row.dataset.index + '][specification][mode]"]');

                categorySelect.addEventListener('change', function () {
                    const m2Radio = itemRadio(row, 'm2');
                    const pcsRadio = itemRadio(row, 'pcs');
                    if (m2Radio) {
                        m2Radio.checked = categorySelect.value === 'printing';
                    }
                    if (pcsRadio) {
                        pcsRadio.checked = categorySelect.value === 'produk';
                    }
                    refreshItemRowUI(row);
                });

                modeRadios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        refreshItemRowUI(row);
                    });
                });

                row.addEventListener('input', function (event) {
                    if (event.target instanceof Element && event.target.matches('input[name*="specification"]')) {
                        refreshItemRowUI(row);
                    }
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

            function itemTotal(row) {
                const qty = parseFloat(itemInput(row, 'qty')?.value) || 0;
                const unitPrice = parseFloat(itemInput(row, 'unit_price')?.value) || 0;

                return selectedMode(row) === 'm2'
                    ? (calcLuas(row) || 0) * unitPrice
                    : qty * unitPrice;
            }

            function computeSubtotal() {
                let subtotal = 0;

                container.querySelectorAll('.item-row').forEach(function (row) {
                    subtotal += itemTotal(row);
                });

                return subtotal;
            }

            function itemTypeLabel(value) {
                if (value === 'printing') {
                    return 'Printing (m²)';
                }

                if (value === 'produk') {
                    return 'Produk/Produk Jadi (pcs)';
                }

                return '-';
            }

            function buildItemSummaryText(row) {
                const mode = selectedMode(row);

                if (mode === 'm2') {
                    const panjang = parseFloat(itemInput(row, 'specification][ukuran_panjang')?.value) || 0;
                    const lebar = parseFloat(itemInput(row, 'specification][ukuran_lebar')?.value) || 0;
                    const quantityAsli = parseFloat(itemInput(row, 'specification][quantity_asli')?.value) || 0;
                    const luas = (panjang * lebar * quantityAsli) || calcLuas(row);
                    const bahan = (itemInput(row, 'specification][bahan')?.value || '').trim();
                    const jenisFinishing = itemSelect(row, 'specification][jenis_finishing')?.value || '';

                    const parts = ['Luas: ' + luas + ' m²'];
                    if (bahan) {
                        parts.push(bahan);
                    }
                    if (jenisFinishing) {
                        parts.push(jenisFinishing);
                    }

                    return parts.join(' — ');
                }

                const qty = parseFloat(itemInput(row, 'qty')?.value) || 0;
                const unit = (itemInput(row, 'unit')?.value || '').trim() || 'pcs';

                return 'Qty: ' + qty + ' ' + unit;
            }

            function formatDateTimeLocal(value) {
                if (!value) {
                    return '-';
                }

                const raw = String(value);

                return raw.includes('T') ? raw.replace('T', ' ').slice(0, 16) : raw;
            }

            function activeServiceLabels() {
                const labels = [];
                const services = [
                    ['printing', 'Printing'],
                    ['kirim', 'Kirim'],
                    ['reklame', 'Reklame'],
                ];

                services.forEach(function (service) {
                    const el = document.querySelector('input[name="services[' + service[0] + ']"]');
                    if (el && el.checked) {
                        labels.push(service[1]);
                    }
                });

                return labels.length ? labels.join(', ') : '-';
            }

            function populateSummaryCustomer() {
                const nameElement = document.getElementById('summary-customer-name');
                const priorityElement = document.getElementById('summary-priority');
                const deadlineElement = document.getElementById('summary-deadline');
                const servicesElement = document.getElementById('summary-services');

                if (customerSelect && customerSelect.options[customerSelect.selectedIndex]) {
                    nameElement.textContent = customerSelect.options[customerSelect.selectedIndex].text.trim();
                }

                const priority = prioritySelect && prioritySelect.options[prioritySelect.selectedIndex];
                priorityElement.textContent = priority && priority.dataset.label ? priority.dataset.label : 'Normal';
                deadlineElement.textContent = formatDateTimeLocal(deadlineInput ? deadlineInput.value : '');
                servicesElement.textContent = activeServiceLabels();
            }

            function populateSummaryItems() {
                const tbody = document.getElementById('summary-items-tbody');

                if (!tbody) {
                    return 0;
                }

                tbody.innerHTML = '';
                let subtotal = 0;

                container.querySelectorAll('.item-row').forEach(function (row) {
                    const total = itemTotal(row);
                    subtotal += total;

                    const numberCell = document.createElement('td');
                    numberCell.textContent = String(tbody.children.length + 1);

                    const titleCell = document.createElement('td');
                    titleCell.textContent = (itemInput(row, 'title')?.value || '').trim() || '-';

                    const typeCell = document.createElement('td');
                    typeCell.textContent = itemTypeLabel(itemSelect(row, 'item_type')?.value);

                    const specCell = document.createElement('td');
                    specCell.textContent = buildItemSummaryText(row);

                    const qtyCell = document.createElement('td');
                    qtyCell.textContent = itemInput(row, 'qty')?.value || '0';

                    const unitCell = document.createElement('td');
                    unitCell.textContent = (itemInput(row, 'unit')?.value || '').trim() || '-';

                    const priceCell = document.createElement('td');
                    priceCell.textContent = formatRupiah(parseFloat(itemInput(row, 'unit_price')?.value) || 0);

                    const totalCell = document.createElement('td');
                    totalCell.textContent = formatRupiah(total);

                    const tr = document.createElement('tr');
                    tr.append(numberCell, titleCell, typeCell, specCell, qtyCell, unitCell, priceCell, totalCell);
                    tbody.appendChild(tr);
                });

                return subtotal;
            }

            function populateSummary() {
                populateSummaryCustomer();
                populateSummaryItems();

                const subtotal = computeSubtotal();
                const discount = parseFloat(document.getElementById('discount')?.value) || 0;
                const deliveryFee = parseFloat(document.getElementById('delivery_fee')?.value) || 0;
                const total = subtotal - discount + deliveryFee;

                document.getElementById('order_subtotal').textContent = formatRupiah(subtotal);
                document.getElementById('order_total').innerHTML = '<strong>' + formatRupiah(total) + '</strong>';
            }

            function validateOrderForm() {
                const entryVisible = entryStep.style.display !== 'none';
                const form = document.getElementById('order-form');

                if (entryVisible && form && typeof form.checkValidity === 'function' && !form.checkValidity()) {
                    if (typeof form.reportValidity === 'function') {
                        form.reportValidity();
                    }

                    return false;
                }

                if (!customerSelect || !customerSelect.value) {
                    return false;
                }

                const rows = container.querySelectorAll('.item-row');
                if (rows.length === 0) {
                    return false;
                }

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const title = (itemInput(row, 'title')?.value || '').trim();
                    const itemType = itemSelect(row, 'item_type')?.value;
                    const qty = parseFloat(itemInput(row, 'qty')?.value) || 0;
                    const unitPrice = parseFloat(itemInput(row, 'unit_price')?.value) || 0;

                    if (!title || !itemType || !(qty > 0) || !(unitPrice >= 0)) {
                        return false;
                    }
                }

                return true;
            }

            function showSummaryStep() {
                populateSummary();
                entryStep.style.display = 'none';
                summaryStep.style.display = 'block';
            }

            function showEntryStep() {
                summaryStep.style.display = 'none';
                entryStep.style.display = 'block';
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

                container.querySelectorAll('.item-row').forEach(function (row) {
                    const preview = row.querySelector('.luas-preview');
                    if (preview) {
                        preview.textContent = calcLuas(row);
                    }
                });
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

            btnLanjutKeRingkasan?.addEventListener('click', function () {
                if (!validateOrderForm()) {
                    return;
                }

                showSummaryStep();
            });

            btnBackEdit?.addEventListener('click', function () {
                showEntryStep();
            });

            document.getElementById('order-form')?.addEventListener('submit', function (event) {
                event.preventDefault();

                if (validateOrderForm()) {
                    document.getElementById('order-form').submit();
                    return;
                }

                showEntryStep();

                // Langkah kembali ke edit -> native constraint validation menampilkan error
                validateOrderForm();
            });

            if (kirimCheckbox) {
                function refreshDeliveryAddress() {
                    if (kirimCheckbox.checked) {
                        deliveryAddressField.style.display = '';
                        deliveryAddressInput.disabled = false;
                    } else {
                        deliveryAddressField.style.display = 'none';
                        deliveryAddressInput.disabled = true;
                    }
                }

                kirimCheckbox.addEventListener('change', refreshDeliveryAddress);
                refreshDeliveryAddress();
            }

            if (reklameCheckbox) {
                function refreshReklameActions() {
                    container.querySelectorAll('.item-actions').forEach(function (group) {
                        const enabled = reklameCheckbox.checked;

                        group.querySelectorAll('label').forEach(function (label) {
                            label.style.display = enabled ? '' : 'none';
                        });

                        group.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
                            input.disabled = ! enabled;
                            if (! enabled) {
                                input.checked = false;
                            }
                        });
                    });
                }

                reklameCheckbox.addEventListener('change', refreshReklameActions);
                refreshReklameActions();
            }

            document.getElementById('add-item').addEventListener('click', function () {
                addItem();
            });
            if (initialItems.length > 0) {
                initialItems.forEach(function (item) {
                    addItem(item);
                });
            } else {
                addItem();
            }

            if (reklameCheckbox) {
                reklameCheckbox.dispatchEvent(new Event('change'));
            }

            updatePreview();
        })();
    </script>
@endsection
