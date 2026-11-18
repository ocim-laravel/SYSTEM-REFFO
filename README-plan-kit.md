# REFFO PLAN KIT

Paket dokumen perencanaan + kontrak kerja untuk **REFFO SYSTEM V1.1**.

Folder ini **belum berisi kode Laravel**. Isinya hanya dokumen rencana dan aturan
agar agent AI tidak keluar dari konteks.

## Isi

```
reffo-plan-kit/
├── AGENTS.md          ← kontrak kerja (auto-load di OpenCode)
├── PROGRESS.md        ← fase aktif + rem tangan
├── opencode.json      ← instructions + permission
└── docs/plan/         ← sumber kebenaran ber-ID
```

## Cara pakai

### 1. Scaffold Laravel lebih dulu

```powershell
cd "D:\NURROKHIM STL\PROJECT"
composer create-project laravel/laravel system-reffo-v2
```

### 2. Salin kit ini ke root project

```powershell
cd "D:\NURROKHIM STL\PROJECT"
Copy-Item "reffo-plan-kit\*" "system-reffo-v2\" -Recurse -Force
```

### 3. Buka project di VS Code

```powershell
cd "D:\NURROKHIM STL\PROJECT\system-reffo-v2"
code .
```

### 4. Mulai sesi OpenCode

Tekan `Ctrl+Alt+O` (atau `Ctrl+Shift+Alt+O`), lalu pakai prompt mulai-sesi
dari `docs/plan/03-roadmap.md` bagian **Protokol Sesi**.

## Urutan fase

```
PH-00 Fondasi (login, role, audit, kalender)
  ↓
PH-01 Pelanggan → PH-02 Admin & Order → PH-03 Designer
  ↓
PH-04 Operator/Printing → PH-05 Finishing/QC → PH-06 Pemasangan
  ↓
PH-07 Finance → PH-08 HRD & Material → PH-09 Realtime & Laporan
```

Setiap fase = **satu divisi, semua fungsinya, sampai bisa dipakai**.
