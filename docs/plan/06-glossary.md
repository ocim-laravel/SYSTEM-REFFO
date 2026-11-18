# 06 — GLOSSARY

> Istilah wajib dipakai konsisten di kode, UI, dan dokumen.
> Kalau agent menemukan istilah baru, tambahkan di sini lewat USULAN PERUBAHAN.

---

## Struktur kerja

| Istilah | Arti |
|---|---|
| **Order** | Satu pesanan pelanggan, berisi satu atau banyak item (BR-01) |
| **Item** | Satu pekerjaan di dalam order, punya spesifikasi, harga, deadline, status, dan riwayat sendiri (BR-02) |
| **Draft order** | Order yang dibuat Designer dan belum divalidasi Admin (BR-03) |
| **Validasi** | Persetujuan Admin agar order boleh masuk antrean desain |
| **Divisi** | Unit kerja: Admin, Designer, Produksi, Lapangan, Keuangan, HRD |
| **Kepala bagian** | Penanggung jawab yang berwenang menyetujui/membagi pekerjaan |
| **Admin Produksi** | Admin yang membagikan pekerjaan ke Operator (BR-08) |

## Desain

| Istilah | Arti |
|---|---|
| **Antrean desain** | Daftar item yang menunggu dikerjakan Designer |
| **Siklus revisi** | Satu kumpulan perubahan dari pelanggan dihitung sebagai satu siklus (BR-04) |
| **Revisi gratis** | Maksimal 3 siklus per item |
| **ACC** | Persetujuan desain oleh pelanggan (via Admin) atau internal |
| **File final** | Versi desain yang sudah ACC dan siap produksi |
| **Change Request (CR)** | Perubahan setelah ACC; wajib evaluasi biaya + deadline + ACC ulang (BR-05) |

## Produksi

| Istilah | Arti |
|---|---|
| **Antrean** | Daftar pekerjaan yang siap dikerjakan Operator |
| **Urgent** | Prioritas tertinggi; didahulukan dari semua antrean (BR-06) |
| **H-1** | Deadline **satu hari kerja** sebelum tenggat, memakai kalender kerja (BR-07) |
| **Reguler** | Pekerjaan tanpa tekanan deadline dekat |
| **Claim / self-assign** | Operator berwenang mengambil pekerjaan dari antrean sendiri (BR-09) |
| **Handover** | Pergantian operator/shift pada satu pekerjaan, wajib beralasan (BR-10) |
| **Reprint / cetak ulang** | Mencetak ulang karena gagal; wajib approval atasan produksi (BR-11) |
| **Finishing** | Pekerjaan setelah cetak (potong, lipat, laminasi, jahit, dll.) |
| **QC** | Quality Control; hasil `pass` atau `fail` |
| **Rework** | Pekerjaan ulang karena gagal QC atau salah proses |
| **Packing** | Pengemasan sebelum diserahkan/dikirim |
| **Pekerjaan terlambat** | Item yang melewati `deadline_at` dan belum `completed` |

## Pengiriman & pemasangan

| Istilah | Arti |
|---|---|
| **Delivery** | Pengiriman barang ke pelanggan |
| **Installation** | Pemasangan reklame di lokasi pelanggan |
| **Pickup** | Pelanggan mengambil sendiri di tempat |
| **Petugas lapangan** | Karyawan yang ditugaskan mengirim/memasang |
| **Bukti penyelesaian** | Foto pekerjaan + konfirmasi petugas REFFO. **Tanpa** tanda tangan pelanggan untuk pengiriman tanpa pemasangan |
| **Biaya kirim/pasang** | Biaya standar yang dikenakan **satu kali per order**, bukan per item (BR-14) |

## Keuangan

| Istilah | Arti |
|---|---|
| **DP** | Uang muka |
| **Pelunasan** | Pembayaran sisa setelah DP |
| **Piutang** | Sisa tagihan yang belum dibayar pelanggan |
| **Biaya tambahan** | Biaya di luar penawaran awal (mis. perubahan spesifikasi) |
| **Refund** | Pengembalian dana ke pelanggan |
| **Biaya aktual pembatalan** | Biaya produksi yang benar-benar sudah dikeluarkan (BR-15) |
| **Nilai kesepakatan** | Nominal penyelesaian pembatalan yang disepakati, tidak otomatis seluruh biaya (BR-15) |

## Sistem

| Istilah | Arti |
|---|---|
| **Kalender kerja** | Daftar tanggal kerja dan hari libur perusahaan |
| **Audit log** | Catatan aktivitas penting: siapa, kapan, apa yang berubah (BR-17) |
| **Override Owner** | Tindakan melampaui aturan oleh Owner, wajib alasan + audit log (BR-18) |
| **Private channel** | Channel realtime yang hanya bisa didengar pengguna berwenang |
| **Gate** | Kriteria yang harus lulus sebelum fase berikutnya boleh dimulai |
| **AC** | Acceptance Criteria — kriteria terima yang bisa diuji |
| **DoD** | Definition of Done — syarat minimal sebuah pekerjaan dianggap selesai |
