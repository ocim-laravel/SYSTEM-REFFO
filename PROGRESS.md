# PROGRESS — REFFO SYSTEM V1.1

> File ini adalah **rem tangan** pengembangan. Agent wajib membacanya lebih dulu.
> Hanya fase aktif yang boleh dikerjakan. Perbarui di akhir setiap sesi.

---

## FASE AKTIF

**PH-00 — FONDASI SISTEM**
Status: **BERJALAN**
Estimasi: Minggu 1–6
Modul: MOD-10 (System Management) + fondasi lintas modul

### Ruang lingkup fase ini (HANYA ini)
- [ ] Instalasi Laravel 13 + MySQL `system_reffo_v2` + Vite/Tailwind/Alpine
- [ ] Layout dasar (sidebar per divisi, header, notifikasi, breadcrumb) *(sidebar per divisi + header selesai; notifikasi/breadcrumb belum)*
- [x] Login / logout / reset password / ubah password *(AC-10-1 selesai + feature test)*
- [x] Role & permission (spatie/laravel-permission) + Policy/Gate *(AC-10-2 & AC-10-3 selesai + feature test)*
- [x] Divisi & jabatan (`divisions`) *(CRUD divisi, `users.division_id`, DivisionPolicy, feature test)*
- [x] CRUD pengguna (aktif/nonaktif, reset password oleh admin) *(AC-10-4 selesai: UserPolicy, UserService, UserController, validasi, halaman & feature test)*
- [x] Audit log (`audit_logs`) mencatat create/update/delete + login *(AC-10-4: create/update/delete/reset user dengan before/after BR-17 selesai; override AC-10-7 belum)*
- [x] Cleanup: kolom `users.role` legacy dihapus via migration baru, spatie jadi sumber kebenaran; `operator@reffo.test` dinonaktifkan *(route `/operator/dashboard` tetap ada tapi ditolak semua role sampai PH-04)*
- [ ] Kalender kerja & hari libur (`work_calendars`) — dasar BR-07
- [ ] Pengaturan sistem (`settings`) — nama perusahaan, logo, jam kerja
- [ ] Penomoran otomatis (order, item, customer, invoice-like)
- [ ] Seeder: divisi, role, user Owner, kalender kerja default *(7 role + 6 divisi + 7 user demo selesai; kalender kerja default belum)*
- [ ] Infrastruktur Reverb: konfigurasi, private channel, otorisasi channel *(konfigurasi dasar ada; private channel & otorisasi role+divisi belum)*
- [ ] Halaman error & middleware dasar (active user, must-change-password)

### JANGAN DISENTUH di fase ini
- Modul pelanggan, order, desain, operator, finishing, pengiriman, keuangan
- Dashboard & laporan
- HRD, material, absensi
- Fitur realtime per divisi (baru infrastrukturnya saja)

### Kriteria selesai fase (gate)
- [ ] AC-10-1 … AC-10-6 lulus (lihat `docs/plan/modules/MOD-10-system.md`) *(AC-10-1 … AC-10-4 selesai; AC-10-5 … AC-10-7 belum)*
- [ ] User dengan 4 role contoh bisa login dan hanya melihat menu divisinya *(menu per 7 role sudah diuji otomatis; demo manual belum)*
- [x] Audit log terbukti mencatat create/update/delete user *(feature test AC-10-4, 12 test / 74 assertion)*
- [x] `php artisan test` hijau, `pint` bersih *(62/62 test, 310 assertion pada sesi ini)*
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
| 2026-11-18 | PH-00 (MOD-10) | AC-10-1 (parsial: login/logout, tolak user nonaktif, feature test) | `app/Http/Controllers/Auth/LoginController.php`, `app/Models/User.php`, `database/factories/UserFactory.php`, `database/seeders/UserSeeder.php`, `database/migrations/2026_11_18_124512_add_is_active_to_users_table.php`, `tests/Feature/Auth/LoginTest.php`, `tests/Feature/ExampleTest.php`, `app/Events/RealtimeNotification.php`, `app/Http/Middleware/EnsureUserHasRole.php`, `bootstrap/app.php`, `PROGRESS.md` | Migrasi & seeder dijalankan (MySQL `DB-sistem-reffo-2`); `php artisan test` hijau (8/27); `pint` bersih. Reset/ubah password wajib, 7 role/permission, audit log, kalender kerja, dsb. masih tertunda. |
| 2026-11-18 | PH-00 (MOD-10) | AC-10-1 (sisa: reset password, ubah password wajib, rate limit login, audit login BR-17) | `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Controllers/Auth/PasswordResetController.php`, `app/Http/Controllers/Auth/PasswordChangeController.php`, `app/Http/Requests/Auth/*.php`, `app/Http/Middleware/EnsurePasswordIsChanged.php`, `app/Models/AuditLog.php`, `app/Models/User.php`, `app/Services/System/AuditLogService.php`, `database/migrations/2026_11_18_130000_add_must_change_password_to_users_table.php`, `database/migrations/2026_11_18_130100_create_audit_logs_table.php`, `database/factories/UserFactory.php`, `routes/web.php`, `bootstrap/app.php`, `resources/views/auth/*.blade.php`, `resources/views/layouts/app.blade.php`, `tests/Feature/Auth/LoginSecurityTest.php`, `tests/Feature/Auth/PasswordResetTest.php`, `tests/Feature/Auth/PasswordChangeTest.php`, `PROGRESS.md` | `php artisan test` hijau (21/21); `pint` bersih; migrasi dijalankan. Role/permission/divisi tidak disentuh. Sisa PH-00: AC-10-2 … AC-10-7. |
| 2026-11-18 | PH-00 (MOD-10) | AC-10-2 (7 role + menu per role) & AC-10-3 (403 pada route terlindungi) | `composer.json`, `composer.lock`, `config/permission.php`, `app/Enums/UserRole.php`, `app/Models/User.php`, `app/Models/Division.php`, `app/Policies/DivisionPolicy.php`, `app/Services/System/MenuService.php`, `app/Services/System/DivisionService.php`, `app/Http/Requests/System/DivisionRequest.php`, `app/Http/Controllers/DashboardController.php`, `app/Http/Controllers/System/DivisionController.php`, `app/Http/Controllers/Controller.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Controllers/Auth/PasswordChangeController.php`, `database/migrations/2026_11_18_135333_create_permission_tables.php`, `database/migrations/2026_11_18_135400_create_divisions_table.php`, `database/migrations/2026_11_18_135500_add_division_id_to_users_table.php`, `database/seeders/RolePermissionSeeder.php`, `database/seeders/DivisionSeeder.php`, `database/seeders/UserSeeder.php`, `database/seeders/DatabaseSeeder.php`, `database/factories/UserFactory.php`, `routes/web.php`, `resources/views/layouts/app.blade.php`, `resources/views/layouts/partials/sidebar.blade.php`, `resources/views/dashboard.blade.php`, `resources/views/system/divisions/*.blade.php`, `tests/Feature/System/*.php`, `PROGRESS.md` | `spatie/laravel-permission` dipasang (paket diizinkan kontrak). 7 role, 14 permission, 6 divisi, 7 akun demo (`reffo12345`). `php artisan test` hijau (42/42, 191 assertion); `pint` bersih; migrasi + seeder MySQL lokal sukses. AC-10-4 … AC-10-7 tidak disentuh. |
| 2026-11-18 | PH-00 (MOD-10) | Tambahan dev: tabel akun demo di halaman login | `app/Services/System/DemoAccountService.php`, `database/seeders/UserSeeder.php`, `resources/views/auth/login.blade.php`, `tests/Feature/Auth/DemoAccountTableTest.php`, `PROGRESS.md` | Tabel email/password + tombol "Isi Form" dan "Salin" (satu klik salin email/password, ada fallback tanpa Clipboard API); hanya dirender di environment `local`/`development`/`testing` (tidak tampil di production). `php artisan test` hijau (46/46, 230 assertion); `pint` bersih; seeder MySQL lokal sukses. |
| 2026-11-18 | PH-00 (MOD-10) | AC-10-4 (CRUD pengguna, must_change_password, audit log BR-17) | `app/Policies/UserPolicy.php`, `app/Services/System/UserService.php`, `app/Http/Requests/System/UserRequest.php`, `app/Http/Requests/System/ResetUserPasswordRequest.php`, `app/Http/Controllers/System/UserController.php`, `app/Providers/AppServiceProvider.php`, `app/Services/System/MenuService.php`, `database/seeders/RolePermissionSeeder.php`, `routes/web.php`, `resources/views/system/users/*.blade.php`, `tests/Feature/System/UserCrudTest.php`, `PROGRESS.md` | Kelola pengguna hanya Owner (sesuai tabel aktor MOD-10) lewat permission `users.view`/`users.manage`. Akun buatan admin & reset password → `must_change_password=true`. Audit `user.created/updated/deleted/password_reset` dengan before/after tanpa password. `php artisan test` hijau (58/58, 304 assertion); `pint` bersih; seeder permission MySQL lokal sukses. AC-10-5/10-6/10-7 & modul lain tidak disentuh. Belum di-commit (worktree masih berisi pekerjaan sesi sebelumnya yang belum di-commit). |
| 2026-11-19 | PH-00 (MOD-10) | Cleanup legacy role: drop kolom `users.role`, nonaktifkan `operator@reffo.test` | `database/migrations/2026_11_19_090000_drop_role_column_from_users_table.php`, `app/Models/User.php`, `app/Http/Middleware/EnsureUserHasRole.php`, `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Controllers/Auth/PasswordChangeController.php`, `app/Services/System/UserService.php`, `database/factories/UserFactory.php`, `database/seeders/UserSeeder.php`, `resources/views/{dashboard.blade.php,layouts/app.blade.php,layouts/partials/sidebar.blade.php,system/users/index.blade.php,system/users/_form.blade.php}`, `tests/Feature/Auth/{LoginTest,LoginSecurityTest,PasswordChangeTest,PasswordResetTest}.php`, `tests/Feature/System/LegacyRoleCleanupTest.php`, `PROGRESS.md` | Migration baru (migration lama tidak diubah): backfill peran ke spatie, nonaktifkan akun operator lama (id 2), drop kolom `role`. Semua pembacaan peran memakai spatie; `role:operator` kini menolak semua user, route `/operator/dashboard` sengaja dibiarkan. `php artisan test` hijau (62/62, 310 assertion); `pint` bersih; `php artisan migrate --force` sukses di MySQL lokal. AC-10-5/10-6/10-7 belum disentuh. |
