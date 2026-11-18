# PROGRESS — REFFO SYSTEM V1.1

> File ini adalah **rem tangan** pengembangan. Agent wajib membacanya lebih dulu.
> Hanya fase aktif yang boleh dikerjakan. Perbarui di akhir setiap sesi.

---

## FASE AKTIF

**PH-00 — FONDASI SISTEM**
Status: **BELUM MULAI**
Estimasi: Minggu 1–6
Modul: MOD-10 (System Management) + fondasi lintas modul

### Ruang lingkup fase ini (HANYA ini)
- [ ] Instalasi Laravel 13 + MySQL `system_reffo_v2` + Vite/Tailwind/Alpine
- [ ] Layout dasar (sidebar per divisi, header, notifikasi, breadcrumb)
- [ ] Login / logout / reset password / ubah password
- [ ] Role & permission (spatie/laravel-permission) + Policy/Gate
- [ ] Divisi & jabatan (`divisions`)
- [ ] CRUD pengguna (aktif/nonaktif, reset password oleh admin)
- [ ] Audit log (`audit_logs`) mencatat create/update/delete + login
- [ ] Kalender kerja & hari libur (`work_calendars`) — dasar BR-07
- [ ] Pengaturan sistem (`settings`) — nama perusahaan, logo, jam kerja
- [ ] Penomoran otomatis (order, item, customer, invoice-like)
- [ ] Seeder: divisi, role, user Owner, kalender kerja default
- [ ] Infrastruktur Reverb: konfigurasi, private channel, otorisasi channel
- [ ] Halaman error & middleware dasar (active user, must-change-password)

### JANGAN DISENTUH di fase ini
- Modul pelanggan, order, desain, operator, finishing, pengiriman, keuangan
- Dashboard & laporan
- HRD, material, absensi
- Fitur realtime per divisi (baru infrastrukturnya saja)

### Kriteria selesai fase (gate)
- [ ] AC-10-1 … AC-10-6 lulus (lihat `docs/plan/modules/MOD-10-system.md`)
- [ ] User dengan 4 role contoh bisa login dan hanya melihat menu divisinya
- [ ] Audit log terbukti mencatat create/update/delete user
- [ ] `php artisan test` hijau, `pint` bersih
- [ ] Demo ke Owner disetujui → boleh lanjut PH-01

---

## RIWAYAT FASE

| Fase | Divisi | Modul | Status |
|---|---|---|---|
| PH-00 | Fondasi Sistem | MOD-10 | **AKTIF** |
| PH-01 | Pelanggan (CRM) | MOD-01 | Menunggu |
| PH-02 | Admin & Order | MOD-02 | Menunggu |
| PH-03 | Designer | MOD-03 | Menunggu |
| PH-04 | Operator / Printing | MOD-04 | Menunggu |
| PH-05 | Finishing / QC / Packing | MOD-05 | Menunggu |
| PH-06 | Reklame / Delivery / Installation | MOD-06 | Menunggu |
| PH-07 | Finance | MOD-07 | Menunggu |
| PH-08 | HRD & Material | MOD-08, MOD-09 | Menunggu |
| PH-09 | Realtime, Laporan, Hardening, Deploy | MOD-11 | Menunggu |

---

## LOG SESI

| Tanggal | Fase | ID selesai | File diubah | Catatan |
|---|---|---|---|---|
| — | — | — | — | belum ada sesi |
