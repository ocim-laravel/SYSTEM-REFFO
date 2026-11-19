# ADR-002 — Format penomoran dan kalender kerja default

**Tanggal:** 19 November 2026
**Status:** Diterima (disetujui Owner, 19 November 2026)

## Konteks

PH-00 (MOD-10) perlu menyediakan penomoran otomatis yang dipakai bersama oleh
MOD-01 (pelanggan) dan MOD-02 (order/item), serta kalender kerja sebagai dasar
perhitungan H-1 (BR-07). Tanpa keputusan tertulis, tiap modul dapat membuat format
nomor dan daftar hari liburnya sendiri, sehingga nomor duplikat dan salah antrean
H-1 baru terdeteksi setelah data masuk produksi.

Owner telah menyetujui dua keputusan pada 19 November 2026:

- format nomor `CUS-2026-0001`;
- **24 Desember 2026** ditetapkan sebagai cuti bersama Hari Raya Natal.

## Keputusan

### 1. Format penomoran otomatis

Format tetap: `{PREFIX}-{TAHUN}-{URUT4}`, contoh `CUS-2026-0001`.

| Prefiks | Pengguna |
|---|---|
| `CUS` | Pelanggan (MOD-01) |
| `ORD` | Order (MOD-02) |
| `ITM` | Item order (MOD-02) |

- Urut mulai dari `0001`, empat digit, naik satu per entri, dan **diulang per tahun**.
- Tahun diambil dari waktu server dengan timezone `Asia/Jakarta`.
- Nomor diambil wajib lewat `NumberGeneratorService` dari baris `number_sequences`
  per prefiks/tahun, dengan `lockForUpdate()` di dalam transaksi. Nomor dari transaksi
  yang gagal ikut di-rollback dan tidak terpakai.
- Modul lain **wajib** memanggil service ini; dilarang membuat format penomoran sendiri.

### 2. Kalender kerja default 2026

- `WorkCalendarSeeder` mengisi tahun 2026 dengan 104 hari akhir pekan,
  17 hari libur nasional, dan 8 hari cuti bersama (total 125 entri).
- **24 Desember 2026 adalah cuti bersama Natal** (`is_workday = false`). Tanggal ini
  yang dipakai, bukan 26 Desember.
- Entri dibuat idempoten dengan `upsert` berdasarkan `date`, dan dapat ditimpa
  melalui CRUD yang dikelola Owner/HRD.
- Kalender ini menjadi sumber bagi `WorkCalendarService::previousWorkday()` untuk
  menentukan H-1 sesuai BR-07.

## Konsekuensi

- Format nomor stabil. Perubahan format wajib mengajukan ADR baru, bukan mengubah
  service secara diam-diam.
- Nomor yang sudah terbit tidak diulang atau direnomor ulang, termasuk saat order
  dibatalkan.
- Antrean H-1 mengikuti kalender yang telah disetujui. Perubahan tanggal oleh Owner/HRD
  langsung memengaruhi perhitungan H-1, sehingga perubahan setelah item masuk produksi
  wajib tercatat di audit log (BR-17).
- Seeder hanya mencakup tahun 2026. Untuk tahun berikutnya, Owner/HRD mengisi melalui
  CRUD; otomatisasi seeder tahunan memerlukan usulan perubahan/ADR baru.
- Tidak ada paket baru dan tidak ada aturan bisnis yang diubah; BR-07 tetap berlaku.

## Referensi

- `docs/plan/modules/MOD-10-system.md` — AC-10-5
- `docs/plan/02-business-rules.md` — BR-07, BR-17
- `docs/plan/05-data-model.md` — kalender kerja dan integritas data
- Implementasi: `app/Enums/NumberPrefix.php`, `app/Services/System/NumberGeneratorService.php`,
  `database/seeders/WorkCalendarSeeder.php`, `app/Services/System/WorkCalendarService.php`
