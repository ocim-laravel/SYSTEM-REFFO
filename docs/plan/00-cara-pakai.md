# 00 — CARA MEMAKAI KIT INI

## Kondisi saat ini

| Hal | Status |
|---|---|
| Laravel | **belum di-scaffold** |
| Folder `system-reffo-versi II` | kosong, siap dihapus |
| PHP | 8.5.12 (XAMPP) — pdo_mysql aktif |
| Composer | 2.11.2 |
| MariaDB | 10.11.16, `root` / tanpa password, port 3306 |
| Node / npm | 26.13.1 / 11.20.0 |
| opencode CLI | v2.3.2, ada di PATH user (`~/.opencode/bin`) |

## Urutan eksekusi

### 1. Bersihkan folder kosong
```powershell
cd "D:\NURROKHIM STL\PROJECT"
Remove-Item "system-reffo-versi II" -Recurse -Force
```

### 2. Scaffold Laravel
```powershell
composer create-project laravel/laravel system-reffo-v2
```

### 3. Buat database
```powershell
& "D:\NURROKHIM STL\XAMPP\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS system_reffo_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Salin kit
```powershell
Copy-Item "reffo-plan-kit\*" "system-reffo-v2\" -Recurse -Force
```

### 5. Konfigurasi `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=system_reffo_v2
DB_USERNAME=root
DB_PASSWORD=
```

```powershell
cd system-reffo-v2
php artisan migrate
```

### 6. Buka di VS Code + mulai sesi
```powershell
code .
```
Lalu `Ctrl+Alt+O`, dan pakai prompt mulai-sesi dari `docs/plan/03-roadmap.md`.

---

## Prompt pertama (PH-00)

```text
Baca PROGRESS.md, AGENTS.md, docs/plan/03-roadmap.md, dan
docs/plan/modules/MOD-10-system.md.

Jangan menulis kode dulu. Laporkan:
1. fase aktif
2. daftar AC yang harus selesai di fase ini
3. 3 hal yang dilarang dikerjakan di fase ini
4. rencana urutan pengerjaan (maks 8 langkah)
```

Setelah laporan itu benar, baru:

```text
Mulai dari AC-10-1 (login/logout). Sertakan feature test.
Jangan menyentuh modul lain.
```

---

## Aturan yang paling sering dilanggar

| Situasi | Yang harus kamu katakan |
|---|---|
| Agent mulai bikin modul lain | "Berhenti. Fase aktif hanya PH-00." |
| Agent menambah package | "Hapus. Ajukan ADR dulu." |
| Agent mengubah `docs/plan/` | Permission akan menahan — **jangan allow** |
| Agent refactor file lama | "Revert. Di luar scope." |
| Sesi makin melebar | Tutup, buka sesi baru, mulai dari PROGRESS.md |

---

## Checklist harian

- [ ] Baca PROGRESS.md
- [ ] Kerjakan satu AC
- [ ] `php artisan test`
- [ ] `./vendor/bin/pint`
- [ ] Perbarui PROGRESS.md
- [ ] Commit `<tipe>(<MOD>): <ringkas> [BR-xx]`
