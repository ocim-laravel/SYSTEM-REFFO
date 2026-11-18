# MOD-07 — Finance

**Fase:** PH-07 · **Divisi:** Keuangan
**Dependensi:** PH-06 (order selesai), PH-02 (total order)
**Aturan:** BR-14, BR-15, BR-17, BR-18

## Tujuan
Mencatat seluruh transaksi keuangan order dan menyajikan piutang secara akurat.

## Aktor & izin
| Aksi | Role |
|---|---|
| Lihat data keuangan | Finance, Owner |
| Catat pembayaran | Finance |
| Catat biaya tambahan | Finance, Admin |
| Batalkan / refund | Finance (approval Owner) |
| Lihat laporan | Finance, Owner |

## Fitur
1. Pembayaran: DP, pelunasan, tambahan, refund
2. Metode pembayaran: tunai, transfer, QRIS (teks bebas + referensi)
3. Piutang per order + umur piutang
4. Biaya tambahan order / item
5. Penyelesaian pembatalan: biaya aktual vs nilai kesepakatan (BR-15)
6. Laporan keuangan per order, per periode, per pelanggan
7. Rekonsiliasi: total order vs total pembayaran
8. Audit log setiap transaksi keuangan

## Halaman
- `/finance/payments` — daftar pembayaran
- `/orders/{id}/payments` — catat pembayaran per order
- `/finance/receivables` — daftar piutang + umur
- `/finance/reports` — laporan periode

## Aturan penerapan
- `paid_amount` = Σ payments (kecuali refund mengurangi).
- Piutang = `total` − `paid_amount`; tidak boleh negatif kecuali ada refund.
- Pembayaran melebihi sisa hanya boleh bertipe `additional` atau `refund`.
- Pembatalan: `actual_cost` ≠ `settlement_amount` — keduanya disimpan.
- Semua nominal `decimal(15,2)`, tidak memakai float.

## Acceptance Criteria
- [ ] **AC-07-1** Order Rp10.000.000 dengan DP Rp4.000.000 → piutang Rp6.000.000.
- [ ] **AC-07-2** Pelunasan Rp6.000.000 → piutang 0, status pembayaran `paid`.
- [ ] **AC-07-3** Pembayaran melebihi sisa ditolak kecuali bertipe `additional`/`refund`.
- [ ] **AC-07-4** Pembatalan menyimpan biaya aktual dan nilai kesepakatan terpisah (BR-15).
- [ ] **AC-07-5** Umur piutang dikelompokkan: 0–30, 31–60, 61–90, >90 hari.
- [ ] **AC-07-6** Setiap transaksi tercatat di audit log dengan pelaku dan waktu (BR-17).
- [ ] **AC-07-7** Role selain Finance & Owner tidak dapat membuka `/finance/*` (403).

## Definisi selesai
Order dengan DP + pelunasan menghasilkan piutang 0 dan laporan periode cocok dengan data.
