# MOD-05 — Finishing / QC / Packing

**Fase:** PH-05 · **Divisi:** Produksi
**Dependensi:** PH-04 (item `printed`)
**Aturan:** BR-12, BR-17

## Tujuan
Mengelola pekerjaan setelah cetak: finishing, pemeriksaan mutu, pekerjaan ulang, dan pengepakan.

## Aktor & izin
| Aksi | Role |
|---|---|
| Lihat antrean finishing | Produksi, Admin Produksi, Owner |
| Ambil / dibagi tugas | Operator finishing |
| Catat progres | Operator pemegang pekerjaan |
| Isi hasil QC | QC / Admin Produksi |
| Tandai rework | QC / Admin Produksi |
| Packing | Operator finishing |

## Fitur
1. Antrean finishing (item `printed` → `finishing`)
2. Pembagian tugas & progres finishing
3. QC: `pass` / `fail` + catatan + pelaku + waktu
4. Rework: alasan, tahap pengulangan (cetak/finishing), status, penyelesai
5. Packing: status `packed` + catatan
6. Guard urutan status (BR-12): `printed → finishing → qc → (rework) → packing → packed`
7. Audit log setiap perubahan status

## Halaman
- `/finishing/queue` — antrean finishing
- `/finishing/{itemId}` — detail, progres, QC, rework, packing

## Aturan penerapan
- Item yang belum `printed` tidak bisa masuk finishing (BR-12).
- QC `fail` wajib mengisi catatan; item masuk `rework`.
- Rework yang kembali ke cetak harus lewat alur cetak ulang (BR-11).
- Packing hanya boleh setelah QC `pass`.

## Acceptance Criteria
- [ ] **AC-05-1** Item `printing` tidak bisa masuk finishing (ditolak) (BR-12).
- [ ] **AC-05-2** QC `fail` mewajibkan catatan; tanpa catatan validasi gagal.
- [ ] **AC-05-3** Item `fail` masuk `rework` dan tercatat di riwayat.
- [ ] **AC-05-4** Setelah rework selesai, QC dapat diulang sampai `pass`.
- [ ] **AC-05-5** Packing hanya bisa dilakukan setelah QC `pass`; status menjadi `packed`.
- [ ] **AC-05-6** Riwayat menampilkan urutan finishing → QC → rework → packing beserta pelaku.

## Definisi selesai
Item gagal QC → rework → lulus QC → packed, tanpa ada riwayat yang hilang.
