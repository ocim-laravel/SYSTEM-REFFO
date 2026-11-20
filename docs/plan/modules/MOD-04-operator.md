# MOD-04 — Operator / Printing

**Fase:** PH-04 · **Divisi:** Produksi
**Dependensi:** PH-03 (item dengan file final)
**Aturan:** BR-06, BR-07, BR-08, BR-09, BR-10, BR-11, BR-17

## Tujuan
Mengelola antrean cetak, pembagian pekerjaan, progres, pergantian shift, dan cetak ulang.

## Aktor & izin
| Aksi | Role |
|---|---|
| Lihat antrean | Operator, Admin Produksi, Owner |
| Membagi pekerjaan | Admin Produksi, Kepala Bagian |
| Ambil pekerjaan (claim) | Operator berwenang |
| Catat progres / hasil | Operator pemegang pekerjaan |
| Approve cetak ulang | Kepala Bagian / Penanggung jawab produksi |

## Fitur
1. Tiga antrean terpisah: **Urgent**, **H-1**, **Reguler** (BR-06)
2. Perhitungan H-1 memakai **hari kerja** dari `work_calendars` (BR-07)
3. Pembagian pekerjaan oleh Admin Produksi (BR-08)
4. Self-claim oleh operator berwenang (BR-09)
5. Pencatatan progres: qty rencana vs qty aktual, mulai/selesai
6. Handover shift: tutup penanggung jawab lama + alasan, buka yang baru (BR-10)
7. Pengajuan cetak ulang + approval atasan (BR-11)
8. Audit log perubahan status produksi

## Halaman
- `/production/queue` — tab Urgent / H-1 / Reguler
- `/production/{itemId}` — detail pekerjaan, progres, handover, reprint
- `/production/reprints` — daftar pengajuan cetak ulang

## Aturan penerapan
- `queue_class` dihitung saat item masuk produksi:
  - Urgent bila prioritas item `urgent`
  - H-1 bila `deadline_at` ≤ akhir hari kerja berikutnya
  - Reguler untuk sisanya
- Satu item hanya punya **satu** operator aktif; handover wajib menutup record lama.
- Reprint tidak boleh mengubah qty aktual tanpa approval.
- Operator tidak bisa mengubah harga, deadline, atau spesifikasi.

## Acceptance Criteria
- [ ] **AC-04-1** Item dengan deadline Senin masuk antrean **H-1** pada hari Jumat (BR-07).
- [ ] **AC-04-2** Deadline setelah tanggal libur → H-1 melompati hari libur.
- [ ] **AC-04-3** Operator biasa tidak bisa membagi pekerjaan ke operator lain (403) (BR-08).
- [ ] **AC-04-4** Operator berwenang bisa claim; operator lain tidak bisa claim item yang sudah dipegang.
- [ ] **AC-04-5** Handover menutup record lama dengan alasan dan membuka record baru (BR-10).
- [ ] **AC-04-6** Cetak ulang tanpa approval tidak mengubah qty aktual (BR-11).
- [ ] **AC-04-7** Setelah qty aktual dicatat, item dapat dipindah ke `printed`.
- [ ] **AC-04-8** Menu dan akses produksi untuk Admin:
  - Admin dapat melihat menu Produksi.
  - Admin dapat mengakses antrean produksi.

## Definisi selesai
Antrean H-1 terbukti benar untuk 3 skenario kalender, termasuk hari libur.
