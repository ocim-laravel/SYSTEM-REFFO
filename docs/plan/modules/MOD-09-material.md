# MOD-09 — Material Management

**Fase:** PH-08 · **Divisi:** Produksi / Gudang
**Dependensi:** PH-04 (item produksi), PH-00 (user, audit)
**Aturan:** BR-16, BR-17

## Tujuan
Mencatat pemakaian material per pekerjaan secara manual sebagai dasar perhitungan biaya
dan kebutuhan bahan.

## Aktor & izin
| Aksi | Role |
|---|---|
| Lihat data material | Produksi, Admin Produksi, Owner |
| Tambah / ubah material | Admin Produksi, Owner |
| Catat pemakaian | Operator / Admin Produksi |
| Lihat riwayat | Produksi, Owner |

## Fitur
1. Data material: kode, nama, satuan, kategori, status aktif, catatan
2. Pencatatan pemakaian per item: material, qty, biaya satuan, pelaku, waktu, catatan
3. Riwayat pemakaian per item & per material
4. Ringkasan kebutuhan material per pekerjaan
5. Audit log perubahan data material

## Halaman
- `/materials` — daftar material
- `/materials/usages` — input & riwayat pemakaian
- `/orders/{id}/materials` — kebutuhan material per order

## Aturan penerapan
- **Tanpa** stok otomatis, tanpa pengurangan inventaris (BR-16).
- Qty > 0; biaya satuan opsional.
- Material nonaktif tidak bisa dipakai di pemakaian baru.
- Pemakaian hanya bisa dicatat untuk item yang sudah masuk produksi.

## Acceptance Criteria
- [ ] **AC-09-1** Admin Produksi dapat menambah material dengan satuan.
- [ ] **AC-09-2** Pemakaian material tercatat per item dengan pelaku dan waktu.
- [ ] **AC-09-3** Material nonaktif tidak muncul di form pemakaian baru.
- [ ] **AC-09-4** Ringkasan kebutuhan material per order menjumlahkan qty per material.
- [ ] **AC-09-5** Tidak ada perhitungan stok otomatis di sistem (sesuai BR-16).
- [ ] **AC-09-6** Perubahan data material tercatat di audit log.

## Definisi selesai
Pemakaian material 1 order tercatat lengkap dan bisa dicetak sebagai kebutuhan bahan.
