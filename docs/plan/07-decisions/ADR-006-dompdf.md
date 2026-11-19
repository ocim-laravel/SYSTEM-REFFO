# ADR-006 — Penggunaan DomPDF untuk ekspor ringkasan order

**Tanggal:** 19 November 2026
**Status:** Diterima (disetujui Owner, 19 November 2026)

## Konteks

AC-02-8 (MOD-02, PH-02) mensyaratkan ekspor ringkasan order dalam bentuk file PDF
yang dapat diunduh dengan nama `ORD-XXXX-XXXX.pdf`. Feature test yang menguji AC ini
memeriksa `content-type: application/pdf` dan nama file yang memuat kode order.

`docs/plan/00-kontrak.md §1` memuat daftar paket yang diizinkan. Library PDF **tidak**
termasuk di dalamnya, sehingga pemasangannya memerlukan ADR baru yang disetujui Owner
(kontrak §1, "DILARANG tanpa ADR baru yang disetujui" dan §3.5).

Laravel tidak menyediakan generator PDF bawaan. Blade hanya menghasilkan HTML,
sehingga pendekatan "cetak lewat browser" tidak menghasilkan file `.pdf` dan gagal
memenuhi AC-02-8 maupun feature test-nya.

## Keputusan

Owner menyetujui pengecualian berikut pada 19 November 2026:

1. Pasang **`barryvdh/laravel-dompdf`** — satu-satunya paket baru yang diizinkan
   untuk keperluan ini (beserta dependency transitive yang wajib).
2. **Tidak ada paket lain** yang boleh dipasang dalam sesi ini. Package chart, audit,
   atau generator PDF alternatif tetap dilarang tanpa ADR terpisah.
3. Kompatibilitas versi terhadap Laravel 13 dan PHP 8.5.12 diverifikasi saat
   `composer require`. Apabila resolutions gagal, agent wajib melapor — tidak boleh
   mengganti paket lain tanpa ADR baru.
4. Implementasi dibatasi pada AC-02-8: view `resources/views/orders/pdf.blade.php`,
   `OrderController::exportPdf()`, route `GET /orders/{order}/pdf` (`orders.pdf`),
   tombol pada detail order, dan feature test.

## Konsekuensi

- `composer.json` dan `composer.lock` berubah. Ini adalah satu-satunya perubahan
  di luar kode MOD-02 yang disengaja dan sudah disetujui.
- ADR ini hanya menambah file baru; tidak ada file `docs/plan/**` lama yang diubah.
- PDF memuat data yang sudah tersedia di `orders` dan `order_items` sesuai
  `05-data-model.md` bagian C — tidak ada kolom atau migrasi baru.
- Ekspor memakai otorisasi `orders.view` lewat `OrderPolicy` — peran tanpa izin
  tetap ditolak 403. Tidak ada data sensitif di luar lingkup order yang dikirim.
- Perubahan apa pun terhadap keputusan ini wajib melalui ADR baru, bukan edit kode
  diam-diam.

## Alternatif yang ditolak

| Alternatif | Alasan |
|---|---|
| HTML + CSS `@media print` | Bukan file `.pdf` / `application/pdf`; gagal AC-02-8 dan feature test |
| mPDF, TCPDF, Snappy | Tetap paket baru di luar daftar; lebih berat / butuh binary eksternal |
| Generator PDF manual tanpa paket | Di luar praktik standar Laravel, risiko tinggi |

## Referensi

- `docs/plan/00-kontrak.md` — §1 (paket diizinkan), §3.5 (perubahan `docs/plan`)
- `docs/plan/modules/MOD-02-order.md` — fitur 9, AC-02-8
- `docs/plan/05-data-model.md` — bagian C (Order)
- `docs/plan/02-business-rules.md` — BR-17 (riwayat aktivitas)
