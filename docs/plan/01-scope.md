# 01 — RUANG LINGKUP SISTEM

> Sumber: proposal bab 4 + 5. Setiap modul punya ID `MOD-xx`.
> Dokumen detail per modul ada di `modules/MOD-xx-*.md`.

---

## Peta modul → divisi → fase

| Modul | Nama | Divisi pemilik | Fase |
|---|---|---|---|
| MOD-01 | Customer / CRM | Admin (Customer Service) | PH-01 |
| MOD-02 | Admin & Order Management | Admin | PH-02 |
| MOD-03 | Designer | Designer | PH-03 |
| MOD-04 | Operator / Printing | Produksi | PH-04 |
| MOD-05 | Finishing / QC / Packing | Produksi | PH-05 |
| MOD-06 | Reklame / Delivery / Installation | Lapangan | PH-06 |
| MOD-07 | Finance | Keuangan | PH-07 |
| MOD-08 | HRD / Attendance | HRD | PH-08 |
| MOD-09 | Material Management | Produksi/Gudang | PH-08 |
| MOD-10 | System Management | Owner / IT | PH-00 |
| MOD-11 | Reports / Analytics | Manajemen | PH-09 |

---

## MOD-01 — Customer / CRM

Mengelola data pelanggan dan riwayat transaksi.

- Data pelanggan (kode, nama, tipe, status aktif/nonaktif)
- Kontak: telepon, WhatsApp, email, alamat
- Riwayat order per pelanggan
- Riwayat komunikasi/transaksi
- Pencarian & filter pelanggan
- Catatan internal pelanggan

## MOD-02 — Admin & Order Management

Mengelola order sejak diterima hingga diserahkan ke divisi berikutnya.

- Pembuatan order (oleh Admin; draft boleh dibuat Designer)
- Order multi-item, tiap item punya spesifikasi sendiri
- Penentuan harga dan biaya per item
- Deadline per order dan per item
- Prioritas per item (Normal / H-1 / Urgent)
- Validasi order sebelum masuk antrean desain (BR-03)
- Pengelolaan status order & item
- Pembatalan order + pencatatan biaya aktual (BR-15)
- Riwayat perubahan status (BR-17)

## MOD-03 — Designer

- Antrean desain
- Pembagian tugas designer
- Upload & pengelolaan file desain (draft & final)
- Riwayat revisi (maks. 3 siklus gratis — BR-04)
- Persetujuan desain / ACC
- Change Request setelah ACC (BR-05)
- Penyerahan file final ke produksi

## MOD-04 — Operator / Printing

- Antrean cetak terpisah: **Urgent / H-1 / Reguler** (BR-06, BR-07)
- Pembagian pekerjaan oleh Admin Produksi / kepala bagian (BR-08)
- Operator berwenang dapat mengambil pekerjaan dari antrean (BR-09)
- Pencatatan penanggung jawab & riwayat pergantian operator/shift (BR-10)
- Pencatatan progres cetak
- Pengajuan cetak ulang + persetujuan atasan produksi (BR-11)

## MOD-05 — Finishing / QC / Packing

- Antrean finishing
- Pembagian tugas finishing
- Pencatatan progres
- Quality Control + hasil pemeriksaan
- Pencatatan pekerjaan ulang (rework)
- Packing & status siap diserahkan

## MOD-06 — Reklame / Delivery / Installation

- Penjadwalan pengiriman
- Penjadwalan pemasangan (hanya setelah produksi selesai — BR-13)
- Penentuan petugas lapangan
- Pencatatan progres
- Upload foto bukti penyelesaian
- Pencatatan penyelesaian
- Biaya kirim/pasang standar **satu kali per order** (BR-14)
- Pengiriman tanpa pemasangan: bukti = foto + konfirmasi petugas REFFO,
  **tanpa** tanda tangan pelanggan

## MOD-07 — Finance

- Pencatatan pembayaran (DP, pelunasan, tambahan, refund)
- Piutang pelanggan
- Riwayat transaksi
- Biaya tambahan
- Pencatatan biaya pembatalan (BR-15)
- Laporan keuangan order

## MOD-08 — HRD / Attendance

- Data karyawan
- Jabatan dan divisi
- Kehadiran manual (BR-16)
- Rekap absensi
- Laporan kehadiran
- Fingerprint **di luar lingkup**

## MOD-09 — Material Management

- Data material
- Pencatatan pemakaian material (manual)
- Riwayat penggunaan
- Kebutuhan material per pekerjaan
- Stok otomatis **di luar lingkup**

## MOD-10 — System Management

- Manajemen pengguna
- Role & permission
- Divisi & jabatan
- Audit log
- Kalender kerja & hari libur
- Pengaturan sistem
- Override Owner dengan alasan + audit log (BR-18)

## MOD-11 — Reports / Analytics

- Dashboard Owner
- Dashboard per divisi
- Laporan order
- Laporan produksi
- Laporan pekerjaan terlambat
- Laporan keuangan
- Laporan aktivitas pengguna
- Analisis performa operasional

---

## DI LUAR LINGKUP V1.1 (eksplisit)

| Yang tidak dibuat | Alasan |
|---|---|
| Mode offline / LAN / sinkronisasi konflik | Sistem wajib online |
| Integrasi fingerprint | Ditunda ke pengembangan berikutnya |
| Stok otomatis / inventaris otomatis | Tahap awal manual |
| WhatsApp/SMS gateway berbayar | Tidak dianggarkan |
| Aplikasi mobile native | Berbasis browser |
| Multi-cabang / multi-tenant | Satu perusahaan |
| Login pelanggan | Pelanggan hanya data, bukan user sistem |
| Tanda tangan digital pelanggan | Hanya untuk pengiriman tanpa pemasangan, tidak diwajibkan |
