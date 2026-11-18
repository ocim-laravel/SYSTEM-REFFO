# CHECKLIST GO-LIVE — REFFO SYSTEM V1.1

> Centang setiap item sebelum production. Sumber: proposal bab 10 & 11.
> File ini **bukan** bagian `docs/plan/` (tidak masuk SSOT) — boleh diubah bebas.

## A. Keamanan & akun
- [ ] Akun demo (`reffo12345` × 7) **tidak** ada di database production (UserSeeder otomatis skip di non-local).
- [ ] Akun `operator@reffo.test` lama (id 2, nonaktif) dihapus manual.
- [ ] Password `Owner` diganti dari default; `must_change_password` diset `true` untuk akun buatan admin.
- [ ] Role & permission final sesuai kebutuhan (tidak ada role sementara).
- [ ] Kolom `role` lama sudah dihapus (cleanup selesai).
- [ ] Override Owner wajib alasan + audit log (AC-10-7).

## B. Konfigurasi `.env` production
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` di-generate ulang di server (jangan copy dari lokal)
- [ ] `APP_URL` = domain resmi (contoh `https://reffomedia.com`)
- [ ] `APP_TIMEZONE=Asia/Jakarta`
- [ ] Database: host, port, nama, user, password production (bukan root kosong)
- [ ] `MAIL_MAILER=smtp` + SMTP host/port/user/password (untuk reset password)
- [ ] `QUEUE_CONNECTION=database` (atau redis)
- [ ] `BROADCAST_CONNECTION=reverb`
- [ ] `FILESYSTEM_DISK=local` (atau s3 bila dipakai)
- [ ] `SESSION_DRIVER=database` (disarankan)

## C. Infrastruktur
- [ ] VPS Hostinger KVM 4 (4 vCPU / 16 GB / 200 GB) aktif
- [ ] Domain `.com` terdaftar & DNS mengarah ke VPS
- [ ] SSL Let's Encrypt terpasang (HTTPS)
- [ ] Nginx + PHP-FPM + MySQL berjalan
- [ ] Reverb berjalan via Supervisor (auto-restart)
- [ ] Queue worker berjalan via Supervisor
- [ ] Backup harian DB + file di lokasi terpisah dari server utama

## D. Deployment
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm run build` (Vite production)
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`
- [ ] `php artisan storage:link`
- [ ] GitHub Actions / deploy script teruji
- [ ] Version control: kode final di-push ke `main`

## E. Pengujian akhir
- [ ] Uji end-to-end 1 order dari pelanggan sampai lunas
- [ ] Uji hak akses: setiap role tidak bisa membuka menu divisi lain
- [ ] Uji reset password via email (SMTP production)
- [ ] Uji realtime: perubahan status muncul < 3 detik
- [ ] Uji backup & restore (pemulihan data)
- [ ] Demo ke Owner disetujui

## F. Pelatihan & serah terima
- [ ] Pelatihan per divisi (Admin, Designer, Produksi, Lapangan, Finance, HRD)
- [ ] Dokumentasi pengguna (manual singkat per divisi)
- [ ] Kontak dukungan ditetapkan
- [ ] Evaluasi 2 minggu setelah go-live

---

**Catatan:** item yang bertanda "belum dikerjakan" di `PROGRESS.md` (AC-10-6, AC-10-7, seeder kalender default) harus selesai sebelum gate PH-00 disetujui.
