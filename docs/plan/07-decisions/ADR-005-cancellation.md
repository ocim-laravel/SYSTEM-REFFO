# ADR-005 — Perilaku pembatalan order (AC-02-5, BR-15)

**Tanggal:** 19 November 2026
**Status:** Diterima (disetujui Owner, 19 November 2026)

## Konteks

Implementasi AC-02-5 (MOD-02) menyisakan lima hal yang tidak ditentukan eksplisit
oleh `docs/plan/02-business-rules.md` BR-15 maupun `docs/plan/modules/MOD-02-order.md`:

1. Apakah item ikut dibatalkan, atau dibiarkan pada status terakhirnya.
2. Apakah tombol Validasi boleh muncul untuk order `draft` (bukan hanya `pending_validation`).
3. Panjang minimal alasan pembatalan.
4. Bolehkah order yang sudah `completed`/`delivering` dibatalkan.
5. Filter tanggal pada daftar order memakai `created_at` atau `deadline_at`.

Owner menyetujui seluruh rekomendasi pada 19 November 2026.

## Keputusan

### 1. Item ikut dibatalkan saat order dibatalkan

- `OrderService::cancel()` menandai **semua item** → `cancelled` dan menulis
  `item_status_histories` per item (integritas #7).
- Alasan: order yang dibatalkan tidak akan dilanjutkan, dan riwayat per item tetap
  dapat ditelusuri (BR-17). Status `cancelled` juga tersedia di `ItemStatus`.
- Perubahan perilaku (mis. item dibiarkan pada status terakhirnya) wajib ADR baru.

### 2. Tombol Validasi muncul untuk `draft` dan `pending_validation`

- Di `resources/views/orders/show.blade.php`, tombol Validasi ditampilkan bila status
  order ∈ {`draft`, `pending_validation`} (ADR-004).
- Konsisten dengan `OrderService::validate()` yang menerima kedua status.

### 3. Alasan pembatalan minimal 5 karakter

- `CancelOrderRequest` dan `OrderService::cancel()` mewajibkan `reason` required,
  min 5, max 2000, di-trim. Pola yang sama dengan `DeleteCustomerRequest` dan
  `OverrideRequest` (BR-18).
- Pelonggaran ke "non-empty" memerlukan ADR baru.

### 4. Pembatalan order `completed`/`delivering` dibiarkan sekarang

- `cancel()` hanya menolak order yang sudah `cancelled` (idempoten).
- Batasan lebih ketat (mis. tidak bisa membatalkan order yang sudah `completed`)
  ditunda ke MOD-06/MOD-07 yang memperjelas lifecycle. AC-02-7 (BR-05, guard `acc`)
  juga butuh MOD-03.

### 5. Filter tanggal memakai `created_at`

- Daftar order (`GET /orders`) memfilter rentang `created_at` (tanggal order dibuat).
- `deadline_at` tetap ditampilkan per item dan per order, tetapi **bukan** filter default.

## Konsekuensi

- Pembatalan menghentikan seluruh item sekaligus; tidak ada item yang "tertinggal"
  pada status lama.
- Akses validasi konsisten dengan ADR-004 (`draft` + `pending_validation`).
- Panjang alasan seragam dengan penghapusan pelanggan dan override Owner.
- Batasan pembatalan pada order final akan diperketat di PH-06/PH-07 bila perlu.
- Tidak ada migration baru dan tidak ada perubahan aturan bisnis (BR-15, BR-17 tetap).

## Referensi

- `docs/plan/modules/MOD-02-order.md` — AC-02-5, tabel aktor
- `docs/plan/02-business-rules.md` — BR-15, BR-17
- `docs/plan/07-decisions/ADR-003-order-implementation.md`
- `docs/plan/07-decisions/ADR-004-order-initial-status.md`
- Implementasi: `app/Services/Order/OrderService.php` (`cancel()`),
  `app/Http/Requests/Order/CancelOrderRequest.php`,
  `app/Http/Controllers/Order/OrderController.php` (`cancel`, `cancelStore`),
  `resources/views/orders/cancel.blade.php`, `resources/views/orders/show.blade.php`,
  `tests/Feature/Order/OrderCancellationTest.php`