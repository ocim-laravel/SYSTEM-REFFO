# ADR-004 — Status awal order tergantung pembuatnya (state machine MOD-02)

**Tanggal:** 19 November 2026
**Status:** Diterima (disetujui Owner, 19 November 2026)

## Konteks

AC-02-3 (MOD-02) berbunyi: *"Draft buatan Designer berstatus `pending_validation`;
Designer tidak bisa memvalidasi (403)"* (BR-03). Sementara state machine di
`docs/plan/05-data-model.md` menyediakan status `draft` sebagai status awal order.
Ada dua tafsir yang masuk akal:

- **(a)** Semua order langsung `pending_validation`; status `draft` jadi status mati.
- **(b)** Status awal **tergantung pembuat**: Admin/Owner membuat → `draft`,
  Designer membuat → `pending_validation`.

Pilihan (a) membuat `OrderStatus::Draft` tidak pernah terjadi di alur nyata
(hanya lewat factory), sehingga integritas #2 dan `assertOrderIsEditable()`
tetap menyebut `draft` padahal tidak pernah dipakai — inkoheren.

Owner memilih **(b)** pada 19 November 2026.

## Keputusan

### 1. Status awal order tergantung peran pembuat

- **Designer** membuat order → status `pending_validation` (langsung menunggu validasi Admin).
- **Admin / Owner** membuat order → status `draft` (masih boleh diedit, lalu divalidasi).
- Peran ditentukan dari aktor yang memanggil `OrderService::create()` via
  `hasRole('designer')`. Role lain (Production, Finance, dll.) tidak boleh membuat
  order (policy `create` = `orders.view`).

### 2. `OrderService::validate()` menerima `draft` dan `pending_validation`

- Admin dapat memvalidasi order berstatus `draft` **maupun** `pending_validation`.
- Setelah validasi: order → `validated` (`validated_by`/`validated_at` terisi) dan
  semua item → `queued_design` + tulis `item_status_histories` (integritas #7).
- Designer tetap 403 (`orders.validate` hanya Admin), sesuai BR-03.
- Owner juga 403 untuk validasi (tabel aktor MOD-02: validasi khusus Admin).
  Owner tetap punya `orders.view` + `orders.manage`; bila Owner harus boleh
  memvalidasi, perlu ADR baru.

### 3. `OrderStatus::Draft` tetap hidup

- Status `draft` dipakai untuk order yang dibuat Admin/Owner sebelum validasi.
- `assertOrderIsEditable()` mengizinkan mutasi item pada status `draft` dan
  `pending_validation` (pra-validasi).
- Transisi item ke `queued_design` di luar `validate()` tetap ditolak
  (integritas #2).

## Konsekuensi

- State machine yang dipakai sesungguhnya: `draft` → (validasi Admin) → `validated`
  dan `pending_validation` → (validasi Admin) → `validated`. Status `draft` dan
  `pending_validation` keduanya merupakan keadaan pra-validasi.
- `OrderStatus::Draft` tidak lagi status mati; factory default tetap `draft`.
- Pengubahan status awal di masa depan (mis. menambah status `draft` untuk Designer
  dengan aksi "kirim untuk validasi") wajib melalui ADR baru.
- Tidak ada migration baru; tidak ada perubahan aturan bisnis (BR-03 tetap berlaku).
- MOD-03 (desain) perlu memperlakukan item `queued_design` sebagai satu-satunya
  sumber antrean desain, tanpa membedakan apakah order-nya berasal dari `draft`
  atau `pending_validation`.

## Referensi

- `docs/plan/modules/MOD-02-order.md` — AC-02-3, AC-02-4, tabel aktor
- `docs/plan/02-business-rules.md` — BR-03
- `docs/plan/05-data-model.md` — "Status (Enum)" `OrderStatus`, "Aturan integritas" #2
- `docs/plan/07-decisions/ADR-003-order-implementation.md` — keputusan skema order
- Implementasi: `app/Services/Order/OrderService.php` (`create()`, `validate()`,
  `initialStatus()`, `assertOrderIsEditable()`), `tests/Feature/Order/OrderAccessAndStateTest.php`,
  `tests/Feature/Order/OrderDomainTest.php`, `resources/views/orders/create.blade.php`