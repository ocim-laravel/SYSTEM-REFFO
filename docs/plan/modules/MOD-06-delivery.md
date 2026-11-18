# MOD-06 — Reklame / Delivery / Installation

**Fase:** PH-06 · **Divisi:** Lapangan
**Dependensi:** PH-05 (item `packed` / produksi selesai)
**Aturan:** BR-13, BR-14, BR-17

## Tujuan
Mengelola penjadwalan, pelaksanaan, bukti, dan penyelesaian pekerjaan lapangan.

## Aktor & izin
| Aksi | Role |
|---|---|
| Buat jadwal kirim/pasang | Admin, Admin Produksi |
| Tugaskan petugas | Admin Produksi |
| Catat progres & bukti | Petugas lapangan |
| Tandai selesai | Admin Produksi / Admin |

## Fitur
1. Tiga tipe pekerjaan: **delivery**, **installation**, **pickup**
2. Penjadwalan: tanggal, jam, alamat, catatan
3. Guard pemasangan hanya setelah produksi selesai (BR-13)
4. Penentuan petugas lapangan (bisa lebih dari satu)
5. Pencatatan progres: berangkat, tiba, selesai
6. Upload **foto bukti** + catatan + konfirmasi petugas REFFO
7. Biaya kirim/pasang **sekali per order** (BR-14)
8. Penyelesaian item → `completed`

## Halaman
- `/deliveries` — daftar jadwal (hari ini, mendatang, selesai)
- `/deliveries/create` — pilih order & item, tipe, jadwal, petugas
- `/deliveries/{id}` — detail, progres, bukti, penyelesaian

## Aturan penerapan
- `installation` ditolak bila masih ada item order yang belum `printed`/`packed` (BR-13).
- `pickup` tidak memerlukan petugas lapangan.
- Bukti wajib minimal **1 foto** untuk delivery dan installation.
- Tanpa pemasangan: **tidak ada** tanda tangan/konfirmasi digital pelanggan;
  cukup foto + konfirmasi petugas.
- `deliveries.fee` disimpan di level order (sekali), bukan per item (BR-14).

## Acceptance Criteria
- [ ] **AC-06-1** Jadwal pemasangan untuk item `printing` ditolak (BR-13).
- [ ] **AC-06-2** Order 3 item dengan 1 jadwal pasang → biaya pasang tercatat **sekali** (BR-14).
- [ ] **AC-06-3** Bukti tanpa foto tidak bisa menyelesaikan pekerjaan.
- [ ] **AC-06-4** Penyelesaian mencatat petugas, waktu, dan foto; item menjadi `completed`.
- [ ] **AC-06-5** Tipe `pickup` bisa diselesaikan tanpa petugas lapangan.
- [ ] **AC-06-6** Struk/ringkasan pekerjaan lapangan bisa dicetak (opsional di fase ini).

## Definisi selesai
1 order reklame 3 item: jadwal pasang dibuat setelah produksi selesai, bukti foto terunggah,
biaya pasang ditagih satu kali.
