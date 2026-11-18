# MOD-08 — HRD / Attendance

**Fase:** PH-08 · **Divisi:** HRD
**Dependensi:** PH-00 (divisi, user)
**Aturan:** BR-16, BR-17

## Tujuan
Mengelola data karyawan dan absensi manual sebagai dasar administrasi HRD.

## Aktor & izin
| Aksi | Role |
|---|---|
| Lihat data karyawan | HRD, Owner |
| Tambah / ubah karyawan | HRD |
| Catat absensi | HRD (manual) |
| Lihat absensi sendiri | Semua user (halaman sendiri) |
| Rekap & laporan | HRD, Owner |

## Fitur
1. Data karyawan: kode, nama, divisi, jabatan, tanggal masuk, status
2. Hubungan opsional karyawan ↔ akun user
3. Absensi manual: tanggal, jam masuk, jam keluar, status
   (`hadir`, `izin`, `sakit`, `alpha`), catatan, pencatat
4. Rekap harian & bulanan (jumlah hadir/izin/sakit/alpha)
5. Laporan kehadiran per divisi & periode
6. Audit log perubahan data karyawan & absensi

## Halaman
- `/hrd/employees` — daftar karyawan
- `/hrd/attendance` — input & rekap absensi
- `/hrd/reports` — laporan periode

## Aturan penerapan
- **Tanpa** integrasi fingerprint dan tanpa mesin absensi (BR-16).
- Satu karyawan satu record absensi per tanggal (unik).
- Jam keluar tidak boleh lebih awal dari jam masuk.
- Karyawan nonaktif tidak muncul di pilihan absensi baru.

## Acceptance Criteria
- [ ] **AC-08-1** HRD dapat menambah karyawan dengan divisi & jabatan.
- [ ] **AC-08-2** Absensi ganda untuk karyawan + tanggal yang sama ditolak.
- [ ] **AC-08-3** Jam keluar < jam masuk ditolak validasi.
- [ ] **AC-08-4** Rekap bulanan menghitung jumlah per status dengan benar.
- [ ] **AC-08-5** User non-HRD tidak dapat mengubah absensi orang lain (403).
- [ ] **AC-08-6** Laporan kehadiran per divisi dapat dicetak/diekspor.

## Definisi selesai
Rekap absensi 1 bulan penuh bisa dicetak dan angkanya cocok dengan input.
