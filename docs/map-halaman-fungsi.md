# Peta Halaman dan Fungsi — REFFO SYSTEM V1.1

Dokumen ini memetakan halaman, route, dan fungsi yang sudah dibangun per fase.
Gunakan dokumen ini agar setiap sesi OpenCode hanya perlu membaca bagian yang relevan.

## Cara pakai

- Baca `PROGRESS.md` untuk mengetahui fase aktif.
- Baca bagian yang sesuai dengan fase di dokumen ini.
- Jangan baca seluruh repository jika hanya mengerjakan satu fase.

## PH-00 — Fondasi Sistem (MOD-10)

Halaman utama:

- `/login` — login pengguna.
- `/logout` — logout.
- `/password/reset` — permintaan reset password.
- `/password/reset/{token}` — form reset password.
- `/password/change` — ubah password wajib.
- `/dashboard` — dashboard umum.
- `/admin/dashboard` — dashboard admin.
- `/operator/dashboard` — dashboard operator, saat ini ditolak semua role sampai PH-04.
- `/notifications` — daftar notifikasi.
- `/system/users` — kelola pengguna.
- `/system/divisions` — kelola divisi.
- `/system/work-calendars` — kelola kalender kerja.
- `/system/settings` — pengaturan sistem.
- `/system/overrides` — override Owner dengan alasan.
- `/broadcast-test` — pengujian realtime.

Fungsi utama:

- login/logout/reset/ubah password;
- role dan permission;
- audit log;
- kalender kerja;
- pengaturan sistem;
- override Owner;
- infrastruktur Reverb.

## PH-01 — Customer / CRM (MOD-01)

Halaman utama:

- `/customers` — daftar pelanggan.
- `/customers/create` — tambah pelanggan.
- `/customers/{customer}` — detail pelanggan.
- `/customers/{customer}/edit` — ubah pelanggan.

Fungsi utama:

- CRUD pelanggan;
- penomoran otomatis;
- status aktif/nonaktif;
- pencarian dan filter;
- catatan internal;
- audit log.

## PH-02 — Admin & Order (MOD-02)

Halaman utama:

- `/orders` — daftar order.
- `/orders/create` — buat order.
- `/orders/{order}` — detail order.
- `/orders/{order}/cancel` — form pembatalan.
- `/orders/{order}/pdf` — unduh ringkasan PDF.

Fungsi utama:

- buat order multi-item;
- item printing mode m²;
- item produk mode pcs;
- layanan printing/kirim/reklame;
- alamat kirim jika layanan kirim dipilih;
- tindakan reklame jika layanan reklame dipilih;
- diskon dan biaya kirim;
- total otomatis;
- validasi order;
- pembatalan order;
- audit log.

## PH-03 — Designer (MOD-03)

Halaman utama:

- `/design/queue` — antrean desain.
- `/design/{item}/upload` — unggah file desain.
- `/design/{item}/upload-form` — form unggah desain.
- `/design/{item}/submit` — kirim draft.
- `/design/{item}/revise` — revisi desain.
- `/design/{item}/approve` — ACC desain.
- `/design/{item}/change-request` — ajukan change request.
- `/design/change-request/{changeRequest}/approve` — setujui change request.
- `/design/change-request/{changeRequest}/reject` — tolak change request.
- `/design/download/{file}` — unduh file desain.
- `/design/file/{file}/download` — unduh file desain.
- `/design/files/{file}/download` — unduh file desain.

Fungsi utama:

- antrean desain;
- claim tugas;
- upload versioned file;
- siklus revisi;
- ACC;
- change request;
- serah terima ke produksi.

## PH-04 — Operator / Printing (MOD-04)

Halaman utama:

- `/operator/dashboard` — dashboard operator.

Fungsi utama:

- antrean urgent/h1/reguler;
- pembagian tugas;
- self-claim;
- pencatatan progres cetak;
- handover shift;
- cetak ulang dengan approval.

## PH-05 — Finishing / QC / Packing (MOD-05)

Belum memiliki halaman khusus pada saat ini.

Fungsi utama yang direncanakan:

- antrean finishing;
- pembagian tugas;
- pencatatan progres;
- QC pass/fail;
- rework;
- packing.

## PH-06 — Reklame / Delivery / Installation (MOD-06)

Belum memiliki halaman khusus pada saat ini.

Fungsi utama yang direncanakan:

- jadwal kirim/pasang/ambil sendiri;
- guard pemasangan setelah produksi selesai;
- penentuan petugas lapangan;
- progres pekerjaan lapangan;
- upload foto bukti;
- biaya kirim/pasang satu kali per order.

## PH-07 — Finance (MOD-07)

Belum memiliki halaman khusus pada saat ini.

Fungsi utama yang direncanakan:

- pencatatan DP/pelunasan/tambahan/refund;
- piutang;
- biaya tambahan;
- penyelesaian pembatalan;
- laporan keuangan.

## PH-08 — HRD & Material (MOD-08, MOD-09)

Belum memiliki halaman khusus pada saat ini.

Fungsi utama yang direncanakan:

- data karyawan;
- absensi manual;
- rekap absensi;
- data material;
- pemakaian material.

## PH-09 — Realtime, Laporan, Hardening, Deploy (MOD-11)

Halaman utama:

- `/admin/dashboard` — dashboard admin.
- `/dashboard` — dashboard umum.
- `/broadcast-test` — pengujian realtime.

Fungsi utama:

- dashboard Owner;
- dashboard per divisi;
- laporan order, produksi, keterlambatan, keuangan, aktivitas;
- uji end-to-end;
- uji hak akses;
- deployment dan pelatihan.
