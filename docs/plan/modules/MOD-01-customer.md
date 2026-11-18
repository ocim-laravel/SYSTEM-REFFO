# MOD-01 — Customer / CRM

**Fase:** PH-01 · **Divisi:** Admin / Customer Service
**Dependensi:** PH-00 (auth, role, audit, kalender)
**Aturan:** BR-17, BR-18

## Tujuan
Satu sumber data pelanggan yang dipakai seluruh modul.

## Aktor & izin
| Aksi | Role |
|---|---|
| Lihat & cari pelanggan | Admin, Admin Produksi, Finance, Owner |
| Tambah / ubah pelanggan | Admin, Owner |
| Nonaktifkan pelanggan | Admin, Owner |
| Hapus permanen | Owner (dengan alasan + audit log) |

## Fitur
1. CRUD pelanggan dengan **kode otomatis** (`CUS-0001`)
2. Data: nama, tipe (perusahaan/individu), telepon, WhatsApp, email, alamat, catatan
3. Status aktif/nonaktif — pelanggan nonaktif **tidak muncul** di pilihan order baru
4. Pencarian & filter: nama, kode, telepon, status, tipe
5. Halaman detail: profil + tab **Riwayat Order** (kosong di fase ini)
6. Audit log create/update/delete

## Halaman
- `/customers` — daftar + filter + tombol tambah
- `/customers/create`, `/customers/{id}/edit`
- `/customers/{id}` — profil + riwayat

## Acceptance Criteria
- [ ] **AC-01-1** Admin dapat menambah pelanggan; kode `CUS-xxxx` dibuat otomatis dan unik.
- [ ] **AC-01-2** Pelanggan nonaktif tidak dapat dipilih saat membuat order baru (diuji di service).
- [ ] **AC-01-3** Pencarian nama/telepon/kode mengembalikan hasil yang benar (feature test).
- [ ] **AC-01-4** Halaman detail menampilkan profil dan tab riwayat order (boleh kosong).
- [ ] **AC-01-5** Update data pelanggan tercatat di `audit_logs` dengan nilai before/after.
- [ ] **AC-01-6** Role Finance tidak dapat mengubah data pelanggan (403).

## Definisi selesai
Semua AC lulus, `php artisan test` hijau, `pint` bersih, PROGRESS.md diperbarui.
