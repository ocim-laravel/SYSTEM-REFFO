# 02 — ATURAN BISNIS

> Semua aturan wajib punya ID. Kode/tiket/commit harus merujuk ID ini.
> Aturan yang tidak tercantum di sini **tidak boleh diasumsikan**.

---

## BR-01 — Order multi-item
Satu order dapat memiliki banyak item pekerjaan.
**Implementasi:** relasi `orders` 1—N `order_items`.
**Uji:** order dengan 3 item menghitung total = jumlah total item.

## BR-02 — Item punya siklus hidup sendiri
Setiap item memiliki spesifikasi, status, deadline, prioritas, dan riwayat kerja sendiri.
**Implementasi:** `order_items.status`, `item_status_histories`.
**Uji:** mengubah status item A tidak mengubah item B.

## BR-03 — Validasi Admin sebelum antrean desain
Designer boleh membuat **draft** order, tetapi Admin wajib memvalidasi sebelum
order/item masuk antrean desain.
**Implementasi:** status order `draft → validated`; transisi ke `design_queue` hanya oleh Admin.
**Uji:** designer tidak bisa memindahkan draft ke antrean desain (403).

## BR-04 — Tiga siklus revisi gratis
- Maksimal **3 siklus** revisi gratis per item.
- **Satu kumpulan perubahan** dari pelanggan = **satu siklus**, walau berisi banyak poin.
- Siklus ke-4 dan seterusnya = Change Request (lihat BR-05).
**Implementasi:** `design_revisions.cycle_no`; validasi di `DesignService`.
**Uji:** siklus ke-4 ditolak dan menawarkan Change Request.

## BR-05 — Change Request setelah ACC
Perubahan setelah ACC diproses sebagai Change Request dan wajib dievaluasi
dampak **biaya** dan **deadline**, lalu butuh **ACC ulang**.
**Implementasi:** `change_requests` (cost_impact, deadline_impact, status, approved_by).
**Uji:** item berstatus ACC tidak bisa diubah tanpa CR yang disetujui.

## BR-06 — Antrean operator dipisah
Dashboard Operator memisahkan **Urgent**, **H-1**, dan **Reguler**.
**Implementasi:** `print_jobs.queue_class` (enum), dihitung saat item masuk produksi.
**Uji:** item dengan deadline besok masuk H-1; deadline jauh masuk Reguler.

## BR-07 — H-1 = satu hari KERJA
H-1 dihitung dari **satu hari kerja** sebelum deadline memakai kalender kerja
perusahaan dan hari libur — bukan satu hari kalender.
**Implementasi:** `WorkCalendarService::previousWorkday()`; sumber `work_calendars`.
**Uji:** deadline Senin → H-1 = Jumat (jika Sabtu/Minggu libur).
**Uji:** deadline setelah tanggal libur → H-1 melompati hari libur.

## BR-08 — Pembagian tugas oleh Admin Produksi
Admin Produksi atau kepala bagian membagikan pekerjaan kepada Operator.
**Implementasi:** `print_jobs.assigned_by`, policy `assign`.
**Uji:** operator biasa tidak bisa menugaskan pekerjaan ke operator lain.

## BR-09 — Operator boleh mengambil pekerjaan
Operator yang berwenang dapat mengambil pekerjaan langsung dari antrean (self-assign).
**Implementasi:** aksi `claim` dengan policy `claim` + validasi kapasitas.
**Uji:** operator berwenang berhasil claim; yang tidak berwenang 403.

## BR-10 — Riwayat penanggung jawab & shift
Sistem mencatat penanggung jawab pekerjaan dan riwayat pergantian operator/shift,
termasuk alasan.
**Implementasi:** `print_job_operators` (operator_id, started_at, ended_at, reason).
**Uji:** handover menutup record lama dan membuka record baru.

## BR-11 — Cetak ulang butuh persetujuan
Cetak ulang diajukan Operator dan harus disetujui kepala bagian / penanggung jawab produksi.
**Implementasi:** `reprint_requests` (reason, qty, approved_by, approved_at, status).
**Uji:** reprint tanpa approval tidak mengubah jumlah hasil.

## BR-12 — Finishing & QC setelah produksi
Finishing dan QC dilakukan setelah proses produksi dinyatakan selesai.
**Implementasi:** guard status item `printed → finishing → qc`.
**Uji:** item belum `printed` tidak bisa masuk finishing.

## BR-13 — Pemasangan setelah produksi selesai
Jadwal pemasangan hanya dapat dibuat setelah produksi dinyatakan selesai.
**Implementasi:** guard pada `DeliveryService::scheduleInstallation()`.
**Uji:** penjadwalan pemasangan untuk item `in_production` ditolak.

## BR-14 — Biaya kirim/pasang satu kali per order
Biaya pengiriman/pemasangan standar dikenakan **satu kali per order**, bukan per item.
**Implementasi:** kolom biaya di level `orders` / `deliveries`, bukan `order_items`.
**Uji:** order 3 item reklame dengan 1 jadwal kirim hanya menagih 1 biaya kirim.

## BR-15 — Pembatalan mencatat biaya aktual
Pembatalan setelah produksi berjalan wajib mencatat biaya produksi yang benar-benar
dikeluarkan dan pekerjaan yang sudah dilakukan. Penyelesaian mengikuti **kesepakatan
pembatalan yang dicatat**, bukan otomatis membebankan seluruh biaya.
**Implementasi:** `order_cancellations` (reason, actual_cost, settlement_amount,
settlement_note, decided_by).
**Uji:** pembatalan menyimpan biaya aktual dan nilai kesepakatan terpisah.

## BR-16 — Material & absensi manual
Penggunaan material dan kehadiran dicatat manual pada tahap awal.
**Implementasi:** form input manual; tanpa mesin, tanpa stok otomatis.
**Uji:** tidak ada perhitungan stok otomatis di V1.1.

## BR-17 — Riwayat aktivitas wajib
Perubahan penting harus memiliki riwayat aktivitas yang dapat ditelusuri.
**Implementasi:** `audit_logs` (user, aksi, model, before/after JSON, IP).
**Uji:** update harga item tercatat dengan nilai sebelum & sesudah.

## BR-18 — Override Owner
Owner dapat melakukan override untuk kondisi tertentu dengan **alasan wajib**
dan tercatat di audit log.
**Implementasi:** aksi `override` khusus role Owner; kolom `reason` wajib.
**Uji:** override tanpa alasan ditolak; override tercatat di audit log.

---

## Matriks aturan → modul

| Aturan | Modul terkait |
|---|---|
| BR-01, BR-02 | MOD-02 |
| BR-03 | MOD-02, MOD-03 |
| BR-04, BR-05 | MOD-03 |
| BR-06, BR-07 | MOD-04 |
| BR-08, BR-09, BR-10, BR-11 | MOD-04 |
| BR-12 | MOD-05 |
| BR-13, BR-14 | MOD-06 |
| BR-15 | MOD-07 |
| BR-16 | MOD-08, MOD-09 |
| BR-17, BR-18 | MOD-10 (lintas modul) |
