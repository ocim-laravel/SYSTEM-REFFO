# MOD-11 — Reports / Analytics

**Fase:** PH-09 · **Divisi:** Manajemen
**Dependensi:** seluruh modul PH-01 … PH-08
**Aturan:** BR-17

## Tujuan
Menyajikan informasi operasional dan keuangan untuk Owner dan tiap divisi.

## Aktor & izin
| Dashboard / Laporan | Role |
|---|---|
| Dashboard Owner (semua data) | Owner |
| Dashboard Admin | Admin |
| Dashboard Designer | Designer |
| Dashboard Produksi | Production |
| Dashboard Lapangan | Field |
| Dashboard Keuangan | Finance |
| Laporan HRD | HRD |

## Fitur
1. **Dashboard Owner**: order hari ini, order terlambat, piutang total, produksi berjalan,
   pekerjaan menunggu approval, aktivitas terbaru
2. **Dashboard divisi**: hanya data divisinya
3. **Laporan order**: per periode, status, pelanggan
4. **Laporan produksi**: antrean, selesai, reprint, rework
5. **Laporan pekerjaan terlambat**: item melewati deadline, dikelompokkan per divisi
6. **Laporan keuangan**: pendapatan, piutang, pembatalan
7. **Laporan aktivitas pengguna**: dari audit log
8. **Analisis performa**: rata-rata waktu desain, produksi, finishing, pengiriman
9. Realtime: dashboard ter-update tanpa refresh (event per divisi)

## Halaman
- `/dashboard` — menyesuaikan role
- `/reports/orders`, `/reports/production`, `/reports/late-jobs`,
  `/reports/finance`, `/reports/activity`

## Aturan penerapan
- Semua laporan memakai query teragregasi; tidak menarik seluruh tabel ke PHP.
- Filter wajib: rentang tanggal. Default: bulan berjalan.
- Ekspor: PDF / Excel (CSV) sederhana.
- Data sensitif (gaji, biaya internal) hanya untuk role berhak.

## Acceptance Criteria
- [ ] **AC-11-1** Dashboard Owner menampilkan 5 angka utama dengan benar untuk data uji.
- [ ] **AC-11-2** Dashboard divisi tidak memuat data divisi lain (diuji per role).
- [ ] **AC-11-3** Laporan pekerjaan terlambat akurat memakai `deadline_at` + kalender kerja.
- [ ] **AC-11-4** Laporan keuangan cocok dengan data `payments` dan `orders`.
- [ ] **AC-11-5** Realtime: perubahan status item muncul di dashboard produksi < 3 detik.
- [ ] **AC-11-6** Ekspor CSV laporan order menghasilkan kolom sesuai definisi.
- [ ] **AC-11-7** Uji end-to-end: 1 order dari pelanggan sampai lunas tampil benar di semua laporan.

## Definisi selesai (gate PH-09)
Uji end-to-end 1 order penuh di server production, semua laporan konsisten.
