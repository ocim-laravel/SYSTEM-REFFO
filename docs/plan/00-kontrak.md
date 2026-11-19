# REFFO SYSTEM V1.1 — KONTRAK PENGEMBANGAN

> File ini dimuat otomatis oleh OpenCode di setiap sesi pada folder ini.
> Jangan dihapus. Perubahan aturan hanya lewat ADR yang disetujui.

---

## 0. Sumber kebenaran

- `docs/plan/` adalah SATU-SATUNYA sumber kebenaran.
- Setiap perubahan kode WAJIB merujuk minimal satu ID:
  `MOD-xx` (modul), `BR-xx` (aturan bisnis), `PH-xx` (fase), `AC-xx-n` (kriteria terima).
- Kebutuhan yang tidak ada di `docs/plan/` **TIDAK BOLEH** diimplementasikan.
  Laporkan sebagai **USULAN PERUBAHAN** dan tunggu persetujuan.
- `docs/plan/00-proposal.md` adalah arsip proposal asli. Turunannya (01–06) yang mengikat.

---

## 1. Stack terkunci

| Komponen | Versi/Teknologi |
|---|---|
| Backend | Laravel 13 |
| PHP | 8.3+ (lokal: 8.5.12 XAMPP) |
| Database | MySQL / MariaDB 10.11 |
| Frontend | Blade + Livewire |
| CSS | Tailwind CSS |
| Interaksi | Alpine.js |
| Realtime | Laravel Reverb + Broadcasting + Echo |
| Background | Laravel Queue |
| File | Laravel Storage |
| VCS | GitHub |

### Paket yang DIIZINKAN
`laravel/framework`, `livewire/livewire`, `spatie/laravel-permission`,
`laravel/reverb`, `laravel/pint`, `phpunit/phpunit`,
`tailwindcss`, `vite`, `alpinejs`, `laravel-echo`, `pusher-js`.

### DILARANG tanpa ADR baru yang disetujui
Filament, Inertia, Vue/React/Svelte, Bootstrap, jQuery,
package chart pihak ketiga, package audit pihak ketiga (audit log dibuat sendiri),
dan package lain di luar daftar di atas.

---

## 2. DI LUAR LINGKUP V1.1 — jangan dibuat

- Mode offline / sinkronisasi lokal-cloud / resolusi konflik
- Integrasi mesin fingerprint
- Stok otomatis & perhitungan inventaris otomatis
- Gateway WhatsApp / SMS berbayar
- Aplikasi mobile native
- Multi-cabang / multi-tenant
- Tanda tangan atau konfirmasi digital pelanggan untuk pengiriman tanpa pemasangan
- Portal/login pelanggan (pelanggan hanya objek data, bukan pengguna sistem)

---

## 3. Batas kerja per sesi

1. Baca `PROGRESS.md` **lebih dulu**, sebelum menyentuh file apa pun.
2. Kerjakan HANYA fase aktif (`PH-xx`) yang tertulis di sana.
3. DILARANG refactor file di luar fase aktif, walau terlihat lebih baik.
4. DILARANG mengubah migration yang sudah pernah dijalankan.
5. DILARANG mengubah `docs/plan/**` — ajukan **USULAN PERUBAHAN** dulu.
6. Satu sesi = satu fase. Jangan mencampur modul/divisi berbeda.
7. Kalau menemukan bug di modul fase sebelumnya: **catat, jangan perbaiki**
   kecuali bug itu memblokir fase aktif.

---

## 4. Aturan bisnis wajib

Detail lengkap: `docs/plan/02-business-rules.md`.

| ID | Ringkas |
|---|---|
| BR-01 | Satu order dapat memiliki banyak item |
| BR-02 | Setiap item punya spesifikasi, status, dan riwayat sendiri |
| BR-03 | Draft order oleh Designer wajib divalidasi Admin sebelum masuk antrean desain |
| BR-04 | Maksimal 3 siklus revisi gratis; 1 kumpulan perubahan = 1 siklus |
| BR-05 | Perubahan setelah ACC = Change Request (evaluasi biaya & deadline, ACC ulang) |
| BR-06 | Antrean Operator dipisah: Urgent, H-1, Reguler |
| BR-07 | H-1 = 1 hari KERJA sebelum deadline, memakai kalender kerja |
| BR-08 | Pembagian pekerjaan oleh Admin Produksi / kepala bagian |
| BR-09 | Operator berwenang boleh mengambil pekerjaan dari antrean |
| BR-10 | Penanggung jawab dan riwayat pergantian operator/shift wajib tercatat |
| BR-11 | Cetak ulang wajib diajukan Operator dan disetujui atasan produksi |
| BR-12 | Finishing dan QC dilakukan setelah produksi selesai |
| BR-13 | Pemasangan hanya dapat dijadwalkan setelah produksi dinyatakan selesai |
| BR-14 | Biaya kirim/pasang standar dikenakan SATU KALI per order |
| BR-15 | Pembatalan mencatat biaya aktual; penyelesaian per kesepakatan |
| BR-16 | Material dan absensi dicatat manual (tahap awal) |
| BR-17 | Perubahan penting wajib memiliki riwayat aktivitas |
| BR-18 | Override Owner wajib alasan + tercatat di audit log |

---

## 5. Konvensi kode

- Business logic di `app/Services`, bukan di Controller.
- Controller maksimal 15 baris per method.
- Validasi di Form Request, bukan di Controller/Model.
- Query lewat Eloquent. `DB::raw` hanya dengan komentar alasan.
- Semua otorisasi lewat Policy/Gate — tidak ada `if (role == ...)` di Blade.
- Enum status memakai PHP Enum di `app/Enums` — tidak ada string status liar.
- Nama tabel snake_case plural; model singular.
- Nama test: `it_<behavior>_when_<condition>`.
- Bahasa UI: Indonesia. Nama variabel/kelas/kode: Inggris.
- Semua nominal uang: `decimal(15,2)`. Tidak memakai float.
- Semua tanggal kerja memakai timezone `Asia/Jakarta`.

---

## 6. Struktur folder yang disepakati

```
app/
├── Enums/            status & tipe
├── Models/
├── Policies/
├── Services/         business logic (wajib)
│   ├── Order/
│   ├── Production/
│   ├── Finance/
│   └── ...
├── Livewire/         komponen per divisi
│   ├── Customer/
│   ├── Order/
│   ├── Design/
│   ├── Production/
│   ├── Finishing/
│   ├── Delivery/
│   ├── Finance/
│   ├── Hrd/
│   ├── Material/
│   └── Report/
└── Events/           event realtime
```

---

## 7. Definisi Selesai (DoD) — wajib semua

- [ ] Feature test untuk perilaku baru ada dan **lulus**
- [ ] `php artisan test` hijau
- [ ] `./vendor/bin/pint` bersih
- [ ] tidak ada TODO/FIXME menggantung di kode baru
- [ ] tidak ada file diubah di luar ruang lingkup fase
- [ ] `PROGRESS.md` diperbarui
- [ ] commit: `<tipe>(<MOD>): <ringkas> [BR-xx]`

---

## 8. Format laporan akhir sesi

1. **ID diselesaikan** — daftar AC/BR
2. **Belum selesai** — beserta alasan
3. **File diubah** — daftar path
4. **Risiko / keputusan** yang butuh persetujuan
5. **Saran langkah berikutnya** (tanpa mengerjakannya)

---

## 9. UI khusus pengembangan — JANGAN dihapus / diubah

- Halaman login (`resources/views/auth/login.blade.php`) menampilkan **tabel akun demo**
  (7 akun role, email, password, tombol Isi Form/Salin) yang dikontrol oleh
  `DemoAccountService::isEnabled()` (hanya tampil di environment local/development/testing).
- **DILARANG** menghapus tabel ini, menonaktifkan `DemoAccountService`, atau mengubah
  `isEnabled()` agar tabel hilang di development.
- Tabel ini adalah **alat bantu pengembangan** agar login & berpindah user mudah.
  Kalau perlu perubahan tampilan, ajukan USULAN PERUBAHAN dulu.
- Penyebab tabel "hilang" biasanya `APP_ENV=production` di `.env` — perbaiki `.env`,
  bukan menghapus tabel.
- Jangan ubah `.env` `APP_ENV` ke `production` di mesin pengembangan.
