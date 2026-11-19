# 03 — ROADMAP PER DIVISI

> Prinsip: **satu fase = satu divisi, semua fungsinya, sampai bisa dipakai.**
> Fondasi sistem dikerjakan lebih dulu (PH-00) sebelum divisi mana pun masuk.
> Satu sesi OpenCode = satu fase. Jangan melompat fase.

---

## Peta fase

```
PH-00 Fondasi Sistem          (MOD-10)                 minggu 1–6
   ↓
PH-01 Pelanggan / CRM         (MOD-01)                 minggu 7–8
   ↓
PH-02 Admin & Order           (MOD-02)                 minggu 9–13
   ↓
PH-03 Designer                (MOD-03)                 minggu 14–17
   ↓
PH-04 Operator / Printing     (MOD-04)                 minggu 18–22
   ↓
PH-05 Finishing / QC / Packing(MOD-05)                 minggu 23–25
   ↓
PH-06 Reklame / Delivery      (MOD-06)                 minggu 26–28
   ↓
PH-07 Finance                 (MOD-07)                 minggu 29–31
   ↓
PH-08 HRD & Material          (MOD-08, MOD-09)         minggu 32–34
   ↓
PH-09 Realtime, Laporan, Deploy (MOD-11 + hardening)   minggu 35–40
```

---

## PH-00 — Fondasi Sistem

**Divisi:** Owner / IT (MOD-10)
**Masuk:** project Laravel 13 + MySQL siap, kit dokumen ini sudah disalin.
**Keluar:** login, role, permission, audit log, kalender kerja, pengaturan, layout,
infrastruktur Reverb — **bukan** fitur divisi.

Ruang lingkup lengkap: lihat `PROGRESS.md` fase aktif.
Kriteria terima: `modules/MOD-10-system.md` (AC-10-1 … AC-10-6).

**Gate:** Owner bisa login sebagai 4 role berbeda dan hanya melihat menu divisinya.

---

## PH-01 — Pelanggan (CRM)

**Divisi:** Admin / Customer Service
**Modul:** MOD-01
**Masuk:** PH-00 disetujui.

Fungsi yang harus utuh dalam satu fase:
1. CRUD pelanggan + penomoran otomatis
2. Kontak (telepon, WA, email, alamat)
3. Status aktif/nonaktif (nonaktif tidak bisa dipakai order baru)
4. Pencarian & filter (nama, kode, telepon, status)
5. Halaman detail pelanggan: profil + riwayat order (kosong di fase ini, terisi di PH-02)
6. Catatan internal
7. Audit log untuk create/update/delete

**AC:** `modules/MOD-01-customer.md`
**Gate:** Admin bisa mendaftarkan 10 pelanggan nyata dan mencarinya kembali.

---

## PH-02 — Admin & Order

**Divisi:** Admin
**Modul:** MOD-02
**Masuk:** PH-01 disetujui.

Fungsi yang harus utuh dalam satu fase:
1. Buat order + pilih pelanggan aktif
2. Tambah/hapus/edit item (multi-item, BR-01)
3. Spesifikasi item per jenis hitung: Printing/m² atau Produk/Produk Jadi/pcs;
   layanan Reklame mengaktifkan catatan tindakan konstruksi/rangka dan pemasangan
4. Harga, diskon, total, DP
5. Deadline order & item; prioritas per item (BR-02)
6. Draft oleh Designer, validasi oleh Admin (BR-03)
7. Status order & item dengan state machine
8. Pembatalan order + biaya aktual & kesepakatan (BR-15)
9. Cetak/ekspor ringkasan order (PDF sederhana)
10. Audit log perubahan harga & status (BR-17)
11. Ringkasan sebelum proses: pelanggan, layanan, item, subtotal, diskon,
    biaya kirim, dan total akhir

Catatan jadwal PH-02:
- Minggu 9–12: fungsi order inti dan gate PH-02.
- Minggu 13: penyempurnaan UI berdasarkan ADR-007 dan ADR-008, termasuk
  order campuran pcs/m², ringkasan sebelum proses, dan format nota/PDF.
- Penyempurnaan ini tetap berada di MOD-02 dan tidak mengubah dependensi
  MOD-03.

**AC:** `modules/MOD-02-order.md`
**Gate:** Admin membuat 1 order 4 item (campur printing & reklame) end-to-end sampai antrean desain.

---

## PH-03 — Designer

**Divisi:** Designer
**Modul:** MOD-03
**Masuk:** PH-02 disetujui.

1. Antrean desain (urut prioritas + deadline)
2. Ambil / dibagikan tugas
3. Upload draft desain (versioned)
4. Catat siklus revisi, maksimal 3 gratis (BR-04)
5. ACC desain → kunci versi final
6. Change Request setelah ACC: biaya + deadline + ACC ulang (BR-05)
7. Serah file final ke produksi
8. Riwayat lengkap per item

**AC:** `modules/MOD-03-designer.md`
**Gate:** 1 item melewati 3 siklus revisi + 1 change request tanpa kehilangan riwayat.

---

## PH-04 — Operator / Printing

**Divisi:** Produksi
**Modul:** MOD-04
**Masuk:** PH-03 disetujui (ada item dengan file final).

1. Antrean terpisah Urgent / H-1 / Reguler (BR-06)
2. Perhitungan H-1 memakai hari kerja (BR-07)
3. Pembagian tugas oleh Admin Produksi (BR-08)
4. Self-claim oleh operator berwenang (BR-09)
5. Pencatatan progres & hasil cetak (qty rencana vs aktual)
6. Handover shift: penanggung jawab & alasannya (BR-10)
7. Pengajuan cetak ulang + approval atasan (BR-11)
8. Audit log perubahan status produksi

**AC:** `modules/MOD-04-operator.md`
**Gate:** Antrean H-1 memuat item dengan deadline hari kerja berikutnya; reprint butuh approval.

---

## PH-05 — Finishing / QC / Packing

**Divisi:** Produksi
**Modul:** MOD-05
**Masuk:** PH-04 disetujui.

1. Antrean finishing
2. Pembagian tugas & progres
3. QC: lulus / gagal + catatan
4. Rework: alasan, tahap pengulangan, penyelesaian
5. Packing & status siap diserahkan
6. Guard BR-12

**AC:** `modules/MOD-05-finishing.md`
**Gate:** Item gagal QC bisa di-rework lalu lulus, riwayatnya utuh.

---

## PH-06 — Reklame / Delivery / Installation

**Divisi:** Lapangan
**Modul:** MOD-06
**Masuk:** PH-05 disetujui.

1. Jadwal kirim / pasang / ambil sendiri
2. Guard: pemasangan hanya setelah produksi selesai (BR-13)
3. Penentuan petugas lapangan
4. Progres pekerjaan lapangan
5. Upload foto bukti + konfirmasi petugas REFFO
6. Biaya kirim/pasang satu kali per order (BR-14)
7. Penyelesaian item/order

**AC:** `modules/MOD-06-delivery.md`
**Gate:** 1 order 3 item reklame dengan 1 jadwal pasang → biaya pasang ditagih sekali.

---

## PH-07 — Finance

**Divisi:** Keuangan
**Modul:** MOD-07
**Masuk:** PH-06 disetujui (ada order selesai).

1. Pencatatan DP, pelunasan, tambahan, refund
2. Piutang & umur piutang
3. Biaya tambahan order
4. Penyelesaian pembatalan (BR-15)
5. Laporan keuangan per order & periode
6. Rekonsiliasi pembayaran vs total order

**AC:** `modules/MOD-07-finance.md`
**Gate:** Order dengan DP + pelunasan menghasilkan piutang 0 dan tercatat benar.

---

## PH-08 — HRD & Material

**Divisi:** HRD & Produksi/Gudang
**Modul:** MOD-08, MOD-09
**Masuk:** PH-07 disetujui.

1. Data karyawan, jabatan, divisi
2. Absensi manual (masuk/pulang/izin/sakit/alpha)
3. Rekap absensi bulanan
4. Data material & satuan
5. Pencatatan pemakaian material per item
6. Riwayat pemakaian & kebutuhan per pekerjaan

**AC:** `modules/MOD-08-hrd.md`, `modules/MOD-09-material.md`
**Gate:** Rekap absensi 1 bulan dan pemakaian material 1 order bisa dicetak.

---

## PH-09 — Realtime, Laporan, Hardening, Deploy

**Divisi:** Manajemen / IT
**Modul:** MOD-11
**Masuk:** PH-08 disetujui.

1. Aktifkan event realtime per divisi (dashboard & antrean)
2. Dashboard Owner + dashboard tiap divisi
3. Laporan: order, produksi, keterlambatan, keuangan, aktivitas pengguna
4. Uji end-to-end seluruh alur order
5. Uji hak akses (setiap role tidak bisa membuka menu lain)
6. Backup, monitoring, deployment VPS, pelatihan

**AC:** `modules/MOD-11-reports.md`
**Gate:** Uji end-to-end 1 order dari pelanggan sampai lunas, di server production.

---

## Protokol sesi (dipakai tiap hari)

### 1) Mulai

```text
Baca PROGRESS.md dan docs/plan/03-roadmap.md.
Sebutkan: fase aktif, MOD yang termasuk, dan 3 hal yang JANGAN disentuh.
Jangan menulis kode dulu.
```

### 2) Kerjakan satu AC

```text
Implementasikan AC-<x> saja. Ikuti BR-<y>. Sertakan feature test.
Jangan sentuh file di luar MOD fase aktif.
Kalau perlu ubah docs/plan, ajukan USULAN PERUBAHAN dulu.
```

### 3) Cek drift

```text
Sebutkan file yang kamu ubah dan AC/BR yang terkait.
Kalau ada file tidak terkait, tandai dan kembalikan perubahannya.
```

### 4) Tutup

```text
Jalankan php artisan test dan ./vendor/bin/pint.
Perbarui PROGRESS.md. Commit: <tipe>(<MOD>): <ringkas> [BR-xx].
Laporkan sesuai format bagian 8 AGENTS.md.
```
