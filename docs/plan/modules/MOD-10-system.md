
**Fase:** PH-00 · **Divisi:** Owner / IT
**Aturan:** BR-07, BR-17, BR-18

## Tujuan
Menyediakan fondasi yang dipakai seluruh modul: identitas, hak akses, jejak aktivitas,
kalender kerja, pengaturan, dan infrastruktur realtime.

## Aktor & izin
| Aksi | Role |
|---|---|
| Kelola pengguna | Owner |
| Kelola role & permission | Owner |
| Lihat audit log | Owner |
| Kelola kalender kerja | Owner, HRD |
| Kelola pengaturan sistem | Owner |
| Override dengan alasan | Owner (BR-18) |

## Ruang lingkup
1. **Auth**: login, logout, reset password, ubah password wajib saat pertama login
2. **Role & permission**: 7 role awal
   `owner`, `admin`, `designer`, `production`, `field`, `finance`, `hrd`
3. **Divisi & jabatan**
4. **CRUD pengguna** + aktif/nonaktif + reset password oleh Owner
5. **Audit log**: create/update/delete penting + login + override
6. **Kalender kerja**: tanggal kerja, hari libur, catatan (dasar BR-07)
7. **Pengaturan sistem**: nama perusahaan, logo, jam kerja, format nomor
8. **Penomoran otomatis**: `CUS-`, `ORD-`, `ITM-`
9. **Layout dasar**: sidebar per divisi, header, notifikasi, breadcrumb
10. **Reverb**: konfigurasi, private channel, otorisasi channel
11. **Middleware**: `auth`, `active`, `must_change_password`, `can:...`

## Halaman
- `/login`, `/password/reset`
- `/dashboard` (placeholder di fase ini)
- `/system/users`, `/system/roles`, `/system/divisions`
- `/system/audit-logs`, `/system/work-calendars`, `/system/settings`

## Aturan penerapan
- Password: minimal 8 karakter, hashed bcrypt.
- Login dibatasi (rate limit) dan dicatat di audit log.
- User nonaktif langsung ditolak middleware `active`.
- Menu yang tidak berhak **tidak dirender**; route tetap dijaga Policy.
- Override Owner wajib kolom alasan; tanpa alasan ditolak (BR-18).
- Private channel wajib memeriksa role + divisi.

## Acceptance Criteria
- [ ] **AC-10-1** Login/logout berfungsi; user nonaktif tidak bisa login.
- [ ] **AC-10-2** 7 role ter-seed; user hanya melihat menu sesuai rolenya.
- [ ] **AC-10-3** User tanpa izin mendapat 403 pada route yang dilindungi (ada feature test).
- [ ] **AC-10-4** Audit log mencatat create/update/delete user dengan before/after.
- [ ] **AC-10-5** Kalender kerja dapat diatur; fungsi `previousWorkday()` lulus test
      (deadline Senin → Jumat, melompati hari libur).
- [ ] **AC-10-6** Realtime: event uji terkirim ke private channel dan hanya diterima user berhak.
- [ ] **AC-10-7** Override tanpa alasan ditolak; dengan alasan tercatat di audit log (BR-18).

## Definisi selesai (gate PH-00)
Owner login sebagai 4 role berbeda, hanya melihat menu divisinya, dan audit log terbukti bekerja.
