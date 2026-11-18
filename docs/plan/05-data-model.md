# 05 — MODEL DATA

> Nama tabel `snake_case` plural. Semua tabel memiliki `id`, `created_at`, `updated_at`.
> Nominal uang: `decimal(15,2)`. Tanggal operasional: timezone `Asia/Jakarta`.

---

## A. Fondasi & akses

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `divisions` | name, code, is_active | 1—N users, employees |
| `users` | name, email, password, division_id, employee_id, is_active, last_login_at, must_change_password | N—N roles |
| `roles` / `permissions` | (spatie/laravel-permission) | N—N users |
| `settings` | key, value, type, group | — |
| `audit_logs` | user_id, action, model_type, model_id, before(json), after(json), reason, ip, user_agent | N—1 users |
| `work_calendars` | date, is_workday, note | — (dasar BR-07) |
| `notifications` | user_id, type, title, body, data(json), read_at | N—1 users |

## B. Pelanggan (MOD-01)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `customers` | code, name, type(perusahaan/individu), phone, whatsapp, email, address, notes, is_active, created_by | 1—N orders |

## C. Order (MOD-02)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `orders` | code, customer_id, created_by, validated_by, validated_at, status, priority, deadline_at, subtotal, discount, tax, total, delivery_fee, notes | 1—N items, payments, deliveries |
| `order_items` | order_id, seq, title, category(printing/reklame), specification(json), qty, unit, unit_price, total, deadline_at, priority, status | 1—N design_jobs, print_jobs, finishing_jobs, material_usages |
| `item_status_histories` | order_item_id, from_status, to_status, changed_by, note | N—1 items |
| `order_cancellations` | order_id, reason, actual_cost, settlement_amount, settlement_note, decided_by, decided_at | 1—1 order |

## D. Desain (MOD-03)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `design_jobs` | order_item_id, designer_id, assigned_by, assigned_at, started_at, finished_at, status, revision_cycle_used | 1—N design_files, design_revisions |
| `design_files` | design_job_id, version, kind(draft/final), path, uploaded_by | N—1 design_jobs |
| `design_revisions` | design_job_id, cycle_no, request_note, requested_by, requested_at | N—1 design_jobs |
| `design_approvals` | design_job_id, design_file_id, approved_by, approved_at, method(internal/customer), note | N—1 design_jobs |
| `change_requests` | order_item_id, design_file_id, reason, cost_impact, deadline_impact, status, approved_by, approved_at | N—1 items |

## E. Produksi (MOD-04)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `print_jobs` | order_item_id, queue_class(urgent/h1/regular), status, assigned_to, assigned_by, qty_planned, qty_actual, started_at, finished_at | 1—N print_job_operators, reprint_requests |
| `print_job_operators` | print_job_id, operator_id, started_at, ended_at, reason | N—1 print_jobs (riwayat shift, BR-10) |
| `reprint_requests` | print_job_id, qty, reason, requested_by, approved_by, approved_at, status, actual_qty | N—1 print_jobs |

## F. Finishing / QC (MOD-05)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `finishing_jobs` | order_item_id, type, status, assigned_to, started_at, finished_at | 1—N qc_records |
| `qc_records` | finishing_job_id, result(pass/fail), notes, checked_by, checked_at | N—1 finishing_jobs |
| `reworks` | order_item_id, qc_record_id, reason, redo_stage, status, done_by, done_at | N—1 items |
| `packings` | order_item_id, packed_by, packed_at, note, status | N—1 items |

## G. Pengiriman & pemasangan (MOD-06)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `deliveries` | order_id, type(delivery/installation/pickup), scheduled_at, scheduled_by, crew(json), status, address, fee | 1—N delivery_items, delivery_proofs |
| `delivery_items` | delivery_id, order_item_id | N—1 deliveries |
| `delivery_proofs` | delivery_id, photo_path, note, uploaded_by, uploaded_at | N—1 deliveries |

> Biaya kirim/pasang ada di `deliveries.fee` / `orders.delivery_fee` — **bukan** per item (BR-14).
> Tanpa pemasangan: bukti = foto + konfirmasi petugas, tanpa tanda tangan pelanggan.

## H. Keuangan (MOD-07)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `payments` | order_id, type(dp/settlement/additional/refund), amount, method, reference, paid_at, recorded_by, note | N—1 orders |
| `order_costs` | order_id, order_item_id(nullable), type, amount, note, recorded_by | N—1 orders |

## I. HRD (MOD-08)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `employees` | code, name, division_id, position, join_date, status, user_id(nullable) | 1—N attendances |
| `attendances` | employee_id, date, check_in, check_out, status(present/permission/sick/absent), note, recorded_by | N—1 employees |

## J. Material (MOD-09)

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `materials` | code, name, unit, category, is_active, note | 1—N material_usages |
| `material_usages` | order_item_id, material_id, qty, unit_cost, used_by, used_at, note | N—1 items / materials |

---

## Status (Enum)

### `OrderStatus`
`draft` → `pending_validation` → `validated` → `in_design` → `design_approved`
→ `in_production` → `finished` → `delivering` → `installing` → `completed`
| `cancelled`

### `ItemStatus`
`draft` → `queued_design` → `designing` → `revision` → `awaiting_acc` → `acc`
→ `queued_production` → `printing` → `printed` → `finishing` → `qc`
→ `rework` → `packing` → `packed` → `scheduled` → `delivering`/`installing`
→ `completed` | `cancelled`

### Enum lain
- `Priority`: `normal`, `h1`, `urgent`
- `PrintQueueClass`: `urgent`, `h1`, `regular`
- `PaymentType`: `dp`, `settlement`, `additional`, `refund`
- `DeliveryType`: `delivery`, `installation`, `pickup`
- `QcResult`: `pass`, `fail`
- `AttendanceStatus`: `present`, `permission`, `sick`, `absent`

---

## Aturan integritas

1. `order_items.order_id` wajib ada; hapus item hanya saat order `draft`.
2. Item tidak bisa masuk `queued_design` bila order belum `validated` (BR-03).
3. `design_revisions.cycle_no` maksimal 3 kecuali via `change_requests` (BR-04, BR-05).
4. `print_jobs.queue_class` dihitung dari `deadline_at` + `work_calendars` (BR-07).
5. `deliveries` tipe `installation` hanya bila semua item order `printed`/`packed` (BR-13).
6. Pembayaran tidak boleh melebihi total order kecuali bertipe `additional`/`refund`.
7. Semua perubahan status item menulis `item_status_histories`.
8. Semua create/update/delete penting menulis `audit_logs`.
