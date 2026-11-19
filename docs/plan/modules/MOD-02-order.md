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
2. Item dapat bercampur dalam satu order dengan metode hitung berbeda:
   `Printing` berbasis m² dan `Produk/Produk Jadi` berbasis pcs. Layanan
   `Reklame` mengaktifkan catatan tindakan item (konstruksi/rangka dan pemasangan).
3. Harga per item, diskon, total order
4. Deadline order + deadline per item
5. Prioritas per item: Normal / H-1 / Urgent
6. Validasi: `draft → pending_validation → validated`
7. State machine order & item (lihat `05-data-model.md`)
8. Pembatalan: alasan, biaya aktual, nilai kesepakatan (BR-15)
9. Ekspor ringkasan order (PDF sederhana)
10. Audit log perubahan harga dan status
11. Tahap ringkasan sebelum proses order

## Halaman
- `/orders` — daftar + filter status/tanggal/pelanggan
- `/orders/create` — form order + item dinamis
- `/orders/{id}` — detail, item, riwayat status, tombol aksi
- `/orders/{id}/cancel` — form pembatalan

## Aturan penerapan
- Item hanya bisa dihapus saat order `draft`.
- `deadline_at` item tidak boleh melebihi deadline order (peringatan, bukan blokir mutlak).
- Total order = Σ (qty × harga) − diskon + biaya kirim (sekali per order, BR-14).
- Perhitungan quantity dilakukan per item; item pcs dan item m² boleh berada
  dalam satu order yang sama.
- Item m² menyimpan quantity asli dan luas efektif (`panjang × lebar × quantity`)
  agar nota dapat menampilkan keduanya.
- Sebelum order diproses, pengguna meninjau data pelanggan, layanan, daftar item,
  subtotal otomatis, diskon editable, biaya kirim editable, dan total akhir.
- Harga satuan adalah harga final item; dapat diisi dari nilai default yang
  tersedia dan diedit manual. Master harga khusus belum menjadi bagian MOD-02.
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
- [ ] **AC-02-9** Satu order dapat mencampur item pcs dan m²; subtotal dan total
      menjumlahkan total setiap item secara benar.
- [ ] **AC-02-10** Ringkasan sebelum proses memuat pelanggan, layanan, item,
      subtotal, diskon, biaya kirim, dan total akhir; nota menampilkan quantity
      asli serta luas tagihan untuk item m².

## Definisi selesai
Semua AC lulus + demo 1 order 4 item (campur printing & reklame) sampai antrean desain.
