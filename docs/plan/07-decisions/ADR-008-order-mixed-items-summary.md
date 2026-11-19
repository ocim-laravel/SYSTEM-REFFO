# ADR-008 — Order campuran pcs/m² dan ringkasan sebelum proses

**Tanggal:** 20 November 2026  
**Status:** Diterima sebagai revisi rencana — menunggu implementasi

## Konteks

Satu order dapat berisi pekerjaan dengan metode hitung yang berbeda. Contohnya,
Produk Jadi dihitung per pcs, sedangkan MMT Banner dihitung berdasarkan luas m².
Form order juga perlu memberi kesempatan pengguna memeriksa seluruh data sebelum
order diproses.

## Keputusan

### 1. Metode hitung berada pada level item

Setiap item memilih metode hitungnya sendiri. Satu order boleh mencampur:

- Produk/Produk Jadi: quantity × harga satuan, satuan `pcs`.
- Printing: panjang × lebar × quantity asli = luas efektif, satuan `m²`.

Rumus item:

```text
Total item = quantity penagihan × harga satuan
```

Rumus order:

```text
Subtotal = jumlah seluruh total item
Total akhir = subtotal - diskon + biaya kirim
```

### 2. Informasi item m² tidak boleh hilang

Untuk item m², sistem menyimpan:

- `quantity_asli` — jumlah lembar/unit yang dikerjakan;
- `ukuran_panjang`;
- `ukuran_lebar`;
- `luas_m2` — quantity penagihan.

Nota menampilkan contoh:

```text
MMT Banner | Ukuran: 2 m × 1 m, Jumlah: 3 lembar | 6 m²
```

### 3. Ringkasan sebelum proses

Alur pembuatan order:

```text
Isi data order → Lanjut ke Ringkasan → Edit diskon/biaya kirim
→ Proses Order → Detail order/nota
```

Ringkasan memuat:

- data pelanggan: nama, prioritas, deadline, jenis layanan;
- daftar item: judul, kategori, spesifikasi, quantity, satuan, harga satuan,
  dan total item;
- subtotal otomatis;
- diskon editable;
- biaya kirim editable;
- total akhir otomatis.

Preview di browser hanya untuk kenyamanan. Backend tetap menghitung ulang
subtotal dan total sebagai sumber kebenaran.

### 4. Tindakan Reklame

Checkbox layanan Reklame mengaktifkan pilihan tindakan:

- konstruksi/rangka;
- pemasangan.

Tindakan disimpan sebagai spesifikasi item dan tidak membuat jadwal pemasangan
di PH-02. Penjadwalan tetap menjadi tanggung jawab MOD-06.

### 5. Harga satuan

Harga satuan adalah harga final per item. Form boleh mengisi nilai default yang
tersedia dan pengguna berwenang dapat mengubahnya manual. Master harga khusus
belum dibuat karena belum ada model data dan aturan harga yang disetujui.

## Dampak

- Tidak ada migration baru.
- `order_items.qty` tetap menyimpan quantity penagihan.
- Detail quantity asli dan luas disimpan di `specification` JSON.
- MOD-02 mendapat AC-02-9 dan AC-02-10.
- Jadwal proyek bertambah satu minggu pada penyempurnaan PH-02; PH-03 hingga
  PH-09 bergeser satu minggu, dengan target akhir tetap minggu ke-40.
- Tidak mengubah scope MOD-03, MOD-04, MOD-05, MOD-06, atau MOD-07.

## Referensi

- `docs/plan/modules/MOD-02-order.md`
- `docs/plan/05-data-model.md`
- `docs/plan/02-business-rules.md` — BR-01, BR-02, BR-14, BR-17
- `docs/plan/07-decisions/ADR-007-order-ui-revision.md`
