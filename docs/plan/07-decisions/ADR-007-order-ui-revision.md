# ADR-007 — Revisi UI pembuatan order (form dinamis & spesifikasi item)

**Tanggal:** 19 November 2026
**Status:** Diterima (disetujui Owner, 19 November 2026)

## Konteks

Setelah PH-02 selesai (AC-02-1 … AC-02-8 lulus, gate disetujui), Owner mengajukan
revisi tampilan pembuatan order yang tidak tercakup oleh `docs/plan/modules/MOD-02-order.md`
maupun `docs/plan/05-data-model.md`. Per kontrak §0, perubahan ini diajukan sebagai
USULAN PERUBAHAN dan baru dilaksanakan setelah persetujuan Owner.

Enam poin disetujui pada 19 November 2026.

## Keputusan

### 1. Diskon tetap ada, dipindah setelah subtotal

- Field diskon **tetap ada**, tetapi ditempatkan **setelah subtotal** terhitung
  (nilai default `0`).
- Rumus total tetap `Σ(qty × unit_price) − diskon + delivery_fee` (BR-14, ADR-003).
- Hanya perubahan **posisi field di UI**, bukan rumus.

### 2. Biaya kirim tetap satu angka total

- Biaya kirim/pasang tetap **satu angka** per order (BR-14).
- Checkbox `printing` / `kirim` / `reklame` adalah **label jenis layanan** yang
  ditagih — **bukan** tiga biaya terpisah.
- Jika `kirim` tercentang, muncul field **alamat** (sebelum catatan).
- Catatan tetap.

### 3. `produk jadi (pcs)` adalah sub-tipe dari `printing`

- `ItemCategory` tetap `printing` / `reklame` — **tidak ada kategori baru**, tidak ada
  migrasi baru (ADR-003: `order_items` tanpa kolom `code`).
- Kategori `printing` punya **dua mode produk**:
  - **Mode pcs** — produk jadi/paket yang dihitung per pcs (x banner, rollbanner,
    stiker A3, A board, cetak poster, dll.).
  - **Mode m²** — produk yang dihitung berdasarkan luas `panjang × lebar × qty`
    (cetak MMT banner, cetak stiker ritrama, cetak spanduk kain, dll.).
- Luas m² dihitung dari field `panjang` dan `lebar` (dalam meter) × `qty`.
- Mode menentukan field yang muncul di UI (lihat §4).

### 4. Spesifikasi item masuk ke `specification` (json)

- Semua field spesifik produk (`bahan`, `ukuran_panjang`, `ukuran_lebar`,
  `jenis_finishing`, `tindakan`) masuk ke `order_items.specification` (kolom json
  yang sudah ada — tidak perlu migrasi).
- Enum baru yang perlu dibuat di `app/Enums/`:
  - `FinishingType` (mis. laminasi, potong, lipat, dll.)
  - `ActionType` (mis. kontruksi/rangka, pemasangan)
- Field yang muncul **bersyarat** di UI:
  - Jika kategori `printing` → muncul: `bahan`, `ukuran panjang`, `ukuran lebar`,
    `qty`, `jenis finishing`, dan `tindakan`.
  - `tindakan` muncul **hanya jika checkbox `reklame` (atas) tercentang**:
    ceklis `kontruksi/rangka` dan `pemasangan`. Jika tidak → field tersembunyi.
  - Jika kategori `produk jadi (pcs)` → hanya `judul`, `qty`, `harga satuan`.
  - Jika kategori `reklame` → field sesuai spesifikasi reklame (sesuai MOD-06).

### 5. `kontruksi/rangka` & `pemasangan` hanya catatan di item (PH-02)

- Pada PH-02, `tindakan` (`kontruksi/rangka`, `pemasangan`) disimpan sebagai
  **catatan di `specification`** untuk keperluan produksi.
- Penjadwalan pemasangan nyata (kru, jadwal, bukti) tetap di MOD-06 (BR-13).
- Tidak ada `deliveries` yang dibuat di PH-02.

### 6. Input pelanggan: link ke `/customers/create` di tab baru

- Form order memakai `<select>` dari pelanggan aktif (seperti sekarang).
- Bila pelanggan tidak ada, tombol "Tambah Pelanggan" membuka `/customers/create`
  di **tab baru** (bukan modal/AJAX).
- Setelah pelanggan baru disimpan, pengguna kembali ke tab order dan refresh
  daftar pelanggan (atau pilih manual).
- Alasan: lebih sederhana, konsisten dengan stack yang diizinkan, tidak menambah
  kompleksitas Livewire/Alpine.

## Konsekuensi

- **Migration baru: tidak ada.** `order_items.specification` (json) sudah cukup;
  enum baru (`FinishingType`, `ActionType`) ditambah di `app/Enums/`.
- **Kode yang akan disentuh**: `StoreOrderRequest`, `OrderController`, `OrderService`
  (minor), `resources/views/orders/create.blade.php` + JS, `OrderPolicy` (minor),
  `OrderPdfTest` (mungkin).
- **Test**: perlu update `OrderAccessAndStateTest` (form dinamis), tambah test
  spesifikasi json.
- **Estimasi**: 1–2 sesi OpenCode.
- **Tidak ada perubahan aturan bisnis** (BR-01, BR-02, BR-14, BR-17 tetap).
- **Tidak ada perubahan `docs/plan/**` lama** — hanya ADR baru.

## Referensi

- `docs/plan/modules/MOD-02-order.md` — fitur, halaman, AC
- `docs/plan/02-business-rules.md` — BR-01, BR-02, BR-14, BR-17
- `docs/plan/05-data-model.md` — bagian C (Order), `specification` (json)
- `docs/plan/07-decisions/ADR-003-order-implementation.md` — keputusan skema order
- `docs/plan/07-decisions/ADR-004-order-initial-status.md` — status awal order
- Implementasi yang akan disentuh: `app/Http/Requests/Order/StoreOrderRequest.php`,
  `app/Http/Controllers/Order/OrderController.php`, `app/Services/Order/OrderService.php`,
  `resources/views/orders/create.blade.php`, `app/Enums/` (baru: `FinishingType`, `ActionType`)