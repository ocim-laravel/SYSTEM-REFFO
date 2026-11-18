# ADR-001 — Stack dan paket yang diizinkan

**Tanggal:** 18 November 2026
**Status:** Diterima

## Konteks

Proposal menetapkan Laravel 13 + MySQL + Blade/Livewire + Reverb.
Tanpa batasan paket, agent cenderung menambah library baru untuk masalah kecil,
yang memperbesar risiko dan mempersulit pemeliharaan oleh satu programmer.

## Keputusan

Stack dan paket dikunci pada daftar di `AGENTS.md` bagian 1.

Paket yang diizinkan:

| Paket | Alasan |
|---|---|
| `laravel/framework` 13.x | Sesuai proposal |
| `livewire/livewire` | Frontend reaktif tanpa JS framework |
| `spatie/laravel-permission` | Role & permission teruji, menghindari implementasi sendiri |
| `laravel/reverb` | Realtime sesuai proposal |
| `laravel/pint`, `phpunit/phpunit` | Kualitas kode & test |
| Tailwind, Vite, Alpine.js, laravel-echo, pusher-js | Frontend & realtime client |

Penambahan paket lain wajib ADR baru yang disetujui Owner.

## Konsekuensi

- Tidak ada Filament/Inertia/Vue/React.
- UI dibangun dengan Blade + Livewire; komponen UI dibuat sendiri.
- Audit log dibuat sendiri (`audit_logs`), tidak memakai package.
- Jika Livewire terbukti tidak cukup untuk satu layar, ajukan ADR baru —
  jangan diam-diam menambah paket.
