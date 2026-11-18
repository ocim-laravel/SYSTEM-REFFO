# 04 — ARSITEKTUR TEKNOLOGI

---

## 4.1 Stack (terkunci)

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 13 |
| Database | MySQL / MariaDB 10.11 (`system_reffo_v2`) |
| Frontend | Blade + Livewire |
| CSS | Tailwind CSS |
| Interaksi | Alpine.js |
| Realtime | Laravel Reverb (WebSocket) |
| Broadcasting | Laravel Broadcasting |
| Client | Laravel Echo + pusher-js |
| Background | Laravel Queue (database driver, tahap awal) |
| File | Laravel Storage (local disk, `storage/app/private`) |
| Version control | GitHub |
| Server | Hostinger VPS KVM 4 |

## 4.2 Konsep arsitektur

- **MySQL = source of truth.** Reverb bukan database.
- Laravel mengelola proses bisnis, validasi, dan hak akses.
- Realtime hanya **memberi tahu**, bukan menyimpan state.
- Event dikirim **setelah transaksi database commit** (`ShouldDispatchAfterCommit`).
- Jika WebSocket terputus: client reconnect lalu **mengambil ulang data dari server**
  (tidak ada replay state dari socket).
- Channel bersifat **private**, otorisasi berdasarkan role + divisi.
- Sistem **wajib online**. Tidak ada mode offline, LAN, atau resolusi konflik.

## 4.3 Alur realtime

```
Aksi pengguna
  → Service (validasi + otorisasi)
  → DB transaction commit
  → Event dispatched after commit
  → Reverb broadcast ke private channel
  → Echo di browser memperbarui tampilan
  → bila gagal: refresh manual tetap benar (server = sumber data)
```

## 4.4 Aturan realtime

1. Satu channel per kebutuhan, contoh: `private-division.production`,
   `private-order.{id}`, `private-user.{id}`.
2. Channel wajib punya otorisasi di `routes/channels.php`.
3. Jangan mengirim data sensitif (harga, gaji) ke channel divisi yang tidak berhak.
4. Untuk data besar: kirim **sinyal** (`item.updated` + id), client fetch detail.
5. Maksimal payload 4 KB per event.
6. Event yang wajib ada:
   - `OrderValidated`, `ItemStatusChanged`
   - `DesignSubmitted`, `DesignApproved`, `ChangeRequestRaised`
   - `PrintAssigned`, `PrintProgressUpdated`, `ReprintApproved`
   - `QcFailed`, `ItemPacked`
   - `DeliveryScheduled`, `DeliveryCompleted`
   - `PaymentRecorded`, `OrderCompleted`

## 4.5 Keamanan

- Login akun pengguna, password hashed (bcrypt), rate limit login.
- Role & permission via `spatie/laravel-permission`, dicek lewat Policy/Gate.
- Middleware `EnsureUserIsActive` — user nonaktif langsung ditolak.
- Private channel + otorisasi per divisi.
- Audit log untuk aksi penting (BR-17, BR-18).
- Validasi di server (Form Request), tidak hanya di browser.
- File desain & bukti foto di disk privat, diakses lewat route ber-otorisasi.
- Backup database harian + salinan terpisah dari server.
- Uji akses: setiap role diuji tidak bisa membuka menu/route role lain.

## 4.6 Struktur menu (per divisi)

```
Dashboard
Pelanggan        (Admin)
Order            (Admin, Designer: draft)
Desain           (Designer)
Produksi         (Operator, Admin Produksi)
Finishing & QC   (Produksi)
Pengiriman       (Lapangan)
Keuangan         (Finance)
HRD              (HRD)
Material         (Produksi/Gudang)
Laporan          (Manajemen, Owner)
Sistem           (Owner)
```

Menu yang tidak berhak **tidak dirender** dan route-nya tetap ditolak di server.

## 4.7 Lingkungan

| Lingkungan | Keterangan |
|---|---|
| Lokal | XAMPP PHP 8.5 / MariaDB 10.11, `php artisan serve` + `npm run dev` |
| Production | VPS Hostinger KVM 4, Nginx + PHP-FPM, MySQL, Reverb via Supervisor |

## 4.8 Konfigurasi wajib

- `APP_TIMEZONE=Asia/Jakarta`
- `QUEUE_CONNECTION=database`
- `BROADCAST_CONNECTION=reverb`
- `FILESYSTEM_DISK=local`
- Nominal uang: `decimal(15,2)`

## 4.9 Yang tidak boleh dilakukan

- Menaruh business logic di Controller atau Model.
- Memakai `float` untuk uang.
- Menyimpan status sebagai string bebas (wajib Enum).
- Query langsung di Blade.
- Membuat channel publik untuk data operasional.
- Menambah package di luar daftar `AGENTS.md` tanpa ADR.
