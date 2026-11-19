# ADR-003 — Keputusan implementasi order (skema & domain)

**Tanggal:** 19 November 2026
**Status:** Diterima (disetujui Owner, 19 November 2026)

## Konteks

Sesi skema + domain MOD-02 (PH-02) menemukan tiga hal yang **tidak ditentukan eksplisit**
oleh `docs/plan/05-data-model.md`, sehingga agent berisiko menebak:

1. `orders.customer_id` menentukan apakah pelanggan ber-order masih bisa dihapus permanen;
2. tipe kolom `order_items.qty` tidak disebut (integer atau desimal);
3. `orders.tax` ada di data model, tetapi rumus total di MOD-02
   ("Total order = Σ (qty × harga) − diskon + biaya kirim") **tidak** menyebut pajak.

Tanpa keputusan tertulis, modul lain (MOD-06 pengiriman, MOD-07 keuangan) bisa
mengambil tafsir berbeda: menghapus pelanggan ber-order, membulatkan qty ke integer,
atau memasukkan pajak ke tagihan.

Owner telah menyetujui tiga keputusan pada 19 November 2026.

## Keputusan

### 1. `orders.customer_id` — RESTRICT (tidak bisa dihapus permanen)

- Foreign key memakai `constrained()` tanpa `nullOnDelete()`: **RESTRICT**.
- Pelanggan yang sudah memiliki order **tidak dapat dihapus permanen** lewat
  `CustomerService::delete()`. Database menolak penghapusan; muncul error FK.
- Alasan: riwayat order dan keuangan pelanggan harus tetap dapat ditelusuri.
  Penghapusan pelanggan ber-order juga bertentangan dengan BR-17 (riwayat aktivitas).
- Pelanggan yang belum pernah punya order tetap dapat dihapus (Owner + alasan wajib).
- Bila di masa depan perlu "lepas" hubungan, solusinya **bukan** menghapus pelanggan,
  melainkan menonaktifkannya (`is_active = false`, MOD-01) — nomor dan riwayat tetap utuh.

### 2. `order_items.qty` — `decimal(15,2)`

- `qty` memakai `decimal(15,2)`, bukan integer.
- Alasan: item reklame sering dihitung per luas (mis. `12,5 m²`), sedangkan item
  printing bisa per `box`/`pcs`. Satu tipe desimal melayani keduanya.
- Nilai disimpan apa adanya; pembulatan hanya saat perhitungan uang
  (bcmath, 2 desimal) — bukan pada input.

### 3. `orders.tax` — `decimal(15,2)`, default `0`, **di luar** rumus total

- Kolom `tax` dibuat `decimal(15,2)` dengan default `0`.
- Rumus total **tetap** mengikuti MOD-02: `total = Σ(qty × unit_price) − discount + delivery_fee`.
  Pajak **tidak** ditambahkan.
- Alasan: MOD-02 "Aturan penerapan" menentukan rumus yang eksplisit; menambahkan pajak
  berarti mengubah aturan bisnis, yang harus lewat usulan perubahan/ADR baru.
- Kolom disediakan agar bila Owner kelak menetapkan pajak, tidak perlu migrasi penambahan
  kolom (perubahan nilai rumus tetap butuh ADR baru).

## Konsekuensi

- Penghapusan pelanggan ber-order akan gagal di level database. Ini **disengaja**;
  MOD-07 (keuangan) dan MOD-06 (pengiriman) wajib memakainya sebagai jaminan data.
- `order_items` **tidak** memiliki kolom `code`, sehingga prefiks `ITM` dari ADR-002
  belum dipakai. Jangan membuat kolom `code` pada `order_items` tanpa ADR baru.
- Perhitungan uang memakai `bcmath` dengan 2 desimal; jangan mengganti dengan float.
- Perubahan ketiga keputusan ini wajib melalui ADR baru, bukan edit kode diam-diam.

## Referensi

- `docs/plan/05-data-model.md` — bagian C (Order) dan "Aturan integritas"
- `docs/plan/modules/MOD-02-order.md` — fitur, aturan penerapan
- `docs/plan/02-business-rules.md` — BR-14 (biaya kirim sekali per order), BR-17 (audit)
- `docs/plan/07-decisions/ADR-002-penomoran-kalender.md` — format nomor `ORD-TAHUN-0001`
- Implementasi: `database/migrations/2026_11_19_13*.php`,
  `app/Services/Order/OrderService.php`, `tests/Feature/Order/OrderDomainTest.php`
