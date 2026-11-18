# 00 — ARSIP PROPOSAL ASLI (REFFO SYSTEM V1.1)

> **Status:** arsip, tidak mengikat secara teknis.
> Dokumen yang mengikat: `01-scope.md`, `02-business-rules.md`, `03-roadmap.md`,
> `04-architecture.md`, `05-data-model.md`, `06-glossary.md`, `modules/*`.
> Jika terjadi perbedaan, dokumen turunan yang menang.

---

## 1. Ringkasan eksekutif

REFFO SYSTEM V1.1 adalah rencana pengembangan sistem informasi terintegrasi untuk
mendukung seluruh kegiatan operasional REFFO Media di bidang **Digital Printing** dan
**Reklame**.

Sistem mengintegrasikan: pelanggan, order, desain, produksi, finishing, QC,
pengiriman, pemasangan, keuangan, HRD, hingga laporan manajemen — dalam satu aplikasi
web berbasis browser.

- Stack: **Laravel 13 + MySQL**, dengan pembaruan **realtime**.
- Target: **40 minggu (9–10 bulan)**, 1 programmer internal, 2–4 jam/hari.
- Dokumen disusun: 18 November 2026.

## 2. Latar belakang

Alur Digital Printing & Reklame saling terkait; satu order bisa berisi banyak item
dengan spesifikasi, proses, tenggat, dan status berbeda. Pencatatan terpisah
menyebabkan: informasi order tidak terpusat, status sulit dipantau, risiko
keterlambatan, riwayat revisi tidak terlacak, pembagian tugas tidak terdokumentasi,
produksi–finishing–pengiriman–pemasangan tidak terintegrasi, dan laporan manual.

## 3. Tujuan

Integrasi proses operasional; database terpusat; pemantauan status order;
koordinasi antarbagian; riwayat aktivitas; pengurangan pencatatan manual;
laporan operasional & keuangan; akses sesuai jabatan/divisi; pembaruan realtime.

## 4. Ruang lingkup

11 modul: **Customer/CRM, Admin & Order, Designer, Operator/Printing,
Finishing/QC/Packing, Reklame/Delivery/Installation, Finance, HRD/Attendance,
Material Management, System Management, Reports/Analytics.**
Detail: `01-scope.md`.

## 5. Aturan bisnis

18 butir prinsip operasional. Detail: `02-business-rules.md`.

## 6. Arsitektur teknologi

Laravel 13, MySQL, Blade + Livewire, Tailwind, Alpine.js, Reverb, Broadcasting,
Echo, Queue, Storage, GitHub, Hostinger VPS.
Detail: `04-architecture.md`.

Ketentuan penting: **sistem wajib online**. Tidak ada mode offline, LAN, atau
sinkronisasi konflik.

## 7. Jadwal awal (arsip)

| Minggu | Tahapan |
|---|---|
| 1–2 | Analisis kebutuhan |
| 3–4 | Perancangan sistem |
| 5–6 | Database dan UI/UX |
| 7–8 | Setup Laravel dan MySQL |
| 9–11 | Pengguna dan keamanan |
| 12–13 | Customer / CRM |
| 14–16 | Admin & Order |
| 17–18 | Designer |
| 19–20 | Revisi dan ACC |
| 21–22 | Operator / Printing |
| 23–24 | Progres produksi |
| 25–26 | Cetak ulang dan shift |
| 27–28 | Finishing dan QC |
| 29 | Penyelesaian produksi |
| 30–31 | Delivery / Installation |
| 32 | Bukti penyelesaian |
| 33–34 | Finance |
| 35 | HRD dan Material |
| 36–37 | Realtime dan laporan |
| 38 | Pengujian menyeluruh |
| 39 | Perbaikan dan keamanan |
| 40 | Deployment dan pelatihan |

> **Catatan:** jadwal di atas adalah arsip. Pelaksanaan memakai pemetaan
> **per divisi** di `03-roadmap.md`.

## 8. Anggaran (arsip, bukan bagian teknis)

| Komponen | Estimasi |
|---|---:|
| Jasa pengembangan | Rp42.000.000 – Rp65.000.000 |
| Hostinger VPS KVM 4 (tahunan, harga perpanjangan) | Rp5.590.800 |
| Domain `.com` (tahunan) | Rp209.900 |
| Backup tahunan | Rp1.200.000 |
| **Total tahun pertama (+cadangan 10%)** | **Rp53.900.770 – Rp79.200.770** |
| Operasional tahun berikutnya | ± Rp7.000.700/tahun |

## 9. Risiko dan mitigasi

| Risiko | Mitigasi |
|---|---|
| Pengerjaan lebih lama | Pengembangan bertahap, evaluasi mingguan |
| Perubahan kebutuhan | Dokumentasi + persetujuan Owner |
| Gangguan server | Backup terpisah, pemantauan |
| Kehilangan data | Backup berkala + uji pemulihan |
| Penyalahgunaan akses | Role, permission, private channel, audit log |
| Internet terputus | Operasional dilanjutkan setelah pulih |
| Beban server naik | Monitoring + upgrade VPS |
| Kesalahan proses | Uji bersama pengguna tiap modul |

## 10. Tahapan persetujuan

1. Owner menyetujui konsep & ruang lingkup
2. Owner menyetujui estimasi anggaran
3. Owner menyetujui hosting & domain
4. Pengembangan dimulai dari fondasi
5. Setiap modul diuji bersama bagian terkait
6. Perubahan ruang lingkup dibahas & disetujui lebih dulu
7. Pengujian menyeluruh
8. Peluncuran + pelatihan
9. Evaluasi pasca-implementasi

## 11. Referensi biaya (diperiksa 18 Nov 2026)

- https://www.hostinger.com/id/hosting-vps
- https://support.hostinger.com/id/articles/6976044
- https://www.hostinger.com/id/domain
- https://www.hostinger.com/id/tld/domain-com

Harga dapat berubah; anggaran bersifat estimasi perencanaan, bukan quotation.
