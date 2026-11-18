# MOD-03 — Designer

**Fase:** PH-03 · **Divisi:** Designer
**Dependensi:** PH-02 (order tervalidasi)
**Aturan:** BR-03, BR-04, BR-05, BR-17

## Tujuan
Mengelola pekerjaan desain, revisi, ACC, dan penyerahan file final ke produksi.

## Aktor & izin
| Aksi | Role |
|---|---|
| Lihat antrean desain | Designer, Admin Produksi, Owner |
| Ambil tugas desain | Designer |
| Upload draft/final | Designer |
| ACC desain | Admin (mewakili pelanggan), Owner |
| Ajukan Change Request | Admin, Designer |

## Fitur
1. Antrean desain diurutkan prioritas lalu deadline
2. Pengambilan tugas (self-claim) atau pembagian oleh Admin
3. Upload file **versioned** (v1, v2, …) — tidak menimpa versi lama
4. Pencatatan siklus revisi: maksimal **3 siklus gratis** (BR-04)
5. Catatan revisi per siklus (satu kumpulan perubahan = satu siklus)
6. ACC → versi terkunci sebagai `final`, item menjadi `acc`
7. Change Request setelah ACC: alasan, dampak biaya, dampak deadline, ACC ulang (BR-05)
8. Serah terima ke produksi: item → `queued_production`
9. Riwayat lengkap per item (siapa, kapan, versi berapa)

## Halaman
- `/design/queue` — antrean desain
- `/design/{itemId}` — detail pekerjaan: file, revisi, ACC, CR
- `/design/{itemId}/upload` — unggah versi baru

## Aturan penerapan
- Revisi ke-4 **ditolak** oleh service dan menawarkan alur Change Request.
- File final tidak bisa diganti; perubahan wajib lewat CR.
- Semua file disimpan di disk privat, diakses lewat route ber-otorisasi.
- Ukuran & tipe file divalidasi (pdf, jpg, png, ai/cdr bila diizinkan).

## Acceptance Criteria
- [ ] **AC-03-1** Antrean desain hanya memuat item `queued_design`, urut prioritas → deadline.
- [ ] **AC-03-2** Designer bisa mengambil tugas; nama & waktu tercatat.
- [ ] **AC-03-3** Upload v2 tidak menghapus v1; keduanya bisa diunduh.
- [ ] **AC-03-4** Siklus revisi ke-4 ditolak dan menampilkan tawaran Change Request (BR-04).
- [ ] **AC-03-5** ACC mengunci versi final; upload baru ke item ACC ditolak (BR-05).
- [ ] **AC-03-6** Change Request menyimpan dampak biaya dan deadline; setelah disetujui item dapat direvisi.
- [ ] **AC-03-7** Setelah ACC, item dapat diserahkan ke produksi (`queued_production`) oleh Admin.
- [ ] **AC-03-8** Riwayat menampilkan urutan versi, revisi, dan ACC beserta pelakunya.

## Definisi selesai
1 item melewati 3 siklus revisi + 1 Change Request tanpa kehilangan riwayat.
