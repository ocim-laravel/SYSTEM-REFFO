# MOD-02 — Admin & Order Management

**Fase:** PH-02 · **Divisi:** Admin
**Dependensi:** PH-01 (pelanggan), PH-00 (auth, role, kalender, audit)
**Aturan:** BR-01, BR-02, BR-03, BR-17, BR-18

## Tujuan
Mengelola order dari diterima sampai diserahkan ke antrean desain.

## Aktor & izin
| Aksi | Role |
|---|---|
| Buat draft order | Admin, Designer |
| Validasi order | Admin (Designer tidak boleh) |
| Ubah harga / diskon | Admin, Owner |
| Batalkan order | Admin (butuh alasan), Owner |

## Fitur
1. Order multi-item: tambah/hapus/edit item sebelum validasi
2. Kategori item: `printing` dan `reklame` dengan field spesifikasi berbeda
3. Harga per item, diskon, total order
4. Deadline order + deadline per item
5. Prioritas per item: Normal / H-1 / Urgent
6. Validasi: `draft → pending_validation → validated`
7. State machine order & item (lihat `05-data-model.md`)
8. Pembatalan: alasan, biaya aktual, nilai kesepakatan (BR-15)
9. Ekspor ringkasan order (PDF sederhana)
10. Audit log perubahan harga dan status

## Halaman
- `/orders` — daftar + filter status/tanggal/pelanggan
- `/orders/create` — form order + item dinamis
- `/orders/{id}` — detail, item, riwayat status, tombol aksi
- `/orders/{id}/cancel` — form pembatalan

## Aturan penerapan
- Item hanya bisa dihapus saat order `draft`.
- `deadline_at` item tidak boleh melebihi deadline order (peringatan, bukan blokir mutlak).
- Total order = Σ (qty × harga) − diskon + biaya kirim (sekali per order, BR-14).
- Draft Designer **tidak** masuk antrean desain sebelum divalidasi Admin (BR-03).

## Acceptance Criteria
- [ ] **AC-02-1** Order dengan 3 item menghitung total benar, termasuk diskon.
- [ ] **AC-02-2** Mengubah status item A tidak mengubah item B (BR-02).
- [ ] **AC-02-3** Draft buatan Designer berstatus `pending_validation`; Designer tidak bisa memvalidasi (403) (BR-03).
- [ ] **AC-02-4** Setelah `validated`, item masuk `queued_design`.
- [ ] **AC-02-5** Pembatalan menyimpan alasan, biaya aktual, dan nilai kesepakatan terpisah (BR-15).
- [ ] **AC-02-6** Setiap perubahan harga tercatat di audit log dengan before/after (BR-17).
- [ ] **AC-02-7** Item yang sudah `acc` tidak bisa diubah tanpa Change Request (BR-05).
- [ ] **AC-02-8** PDF ringkasan order memuat pelanggan, item, total, deadline.

## Definisi selesai
Semua AC lulus + demo 1 order 4 item (campur printing & reklame) sampai antrean desain.
