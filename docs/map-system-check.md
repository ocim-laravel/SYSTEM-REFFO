# Peta Cek Sistem — REFFO SYSTEM V1.1

Dokumen ini dibuat agar pengecekan sistem lebih cepat, terarah, dan hemat token.
Baca hanya bagian yang sesuai dengan fase aktif.

## Cara pakai

1. Baca `PROGRESS.md` untuk mengetahui fase aktif.
2. Buka bagian fase yang sesuai di dokumen ini.
3. Cek hanya route, controller, service, model, view, dan test yang terkait.
4. Jangan baca seluruh repository.

## PH-00 — Fondasi

Cek:
- Login: /login
- Logout: /logout
- Password: /password/change, /password/reset
- Dashboard: /dashboard, /admin/dashboard
- Pengguna: /system/users
- Divisi: /system/divisions
- Kalender: /system/work-calendars
- Pengaturan: /system/settings
- Override: /system/overrides
- Notifikasi: /notifications

File utama:
- app/Http/Controllers/Auth
- app/Http/Controllers/System
- app/Models/User.php
- app/Models/Division.php
- app/Models/Setting.php
- app/Models/WorkCalendar.php
- app/Policies
- app/Services/System
- resources/views/auth
- resources/views/system
- tests/Feature/Auth
- tests/Feature/System

## PH-01 — Customer

Cek:
- Daftar: /customers
- Tambah: /customers/create
- Detail: /customers/{customer}
- Edit: /customers/{customer}/edit

File utama:
- app/Http/Controllers/Customer
- app/Models/Customer.php
- app/Policies/CustomerPolicy.php
- app/Services/Customer
- resources/views/customers
- tests/Feature/Customer

## PH-02 — Order

Cek:
- Daftar: /orders
- Buat: /orders/create
- Detail: /orders/{order}
- Batal: /orders/{order}/cancel
- PDF: /orders/{order}/pdf

File utama:
- app/Http/Controllers/Order
- app/Models/Order.php
- app/Models/OrderItem.php
- app/Models/OrderCancellation.php
- app/Policies/OrderPolicy.php
- app/Services/Order
- resources/views/orders
- tests/Feature/Order

## PH-03 — Designer

Cek:
- Antrean: /design/queue
- Upload: /design/{item}/upload
- Submit: /design/{item}/submit
- Revisi: /design/{item}/revise
- ACC: /design/{item}/approve
- Change Request: /design/{item}/change-request
- Unduh file: /design/files/{file}/download

File utama:
- app/Http/Controllers/Design
- app/Models/DesignJob.php
- app/Models/DesignFile.php
- app/Models/DesignRevision.php
- app/Models/DesignApproval.php
- app/Models/ChangeRequest.php
- app/Policies/DesignPolicy.php
- app/Services/Design
- resources/views/design
- tests/Feature/Design

## PH-04 — Operator

Cek:
- Dashboard: /operator/dashboard

File utama:
- app/Http/Controllers/Operator
- app/Models/PrintJob.php
- app/Services/Production
- resources/views/operator

## PH-05 — Finishing

Belum ada halaman khusus.

File yang akan dicek nanti:
- app/Http/Controllers/Finishing
- app/Models/FinishingJob.php
- app/Models/QcRecord.php
- app/Models/Rework.php
- app/Models/Packing.php
- app/Services/Finishing
- resources/views/finishing

## PH-06 — Reklame / Delivery

Belum ada halaman khusus.

File yang akan dicek nanti:
- app/Http/Controllers/Delivery
- app/Models/Delivery.php
- app/Models/DeliveryItem.php
- app/Models/DeliveryProof.php
- app/Services/Delivery
- resources/views/delivery

## PH-07 — Finance

Belum ada halaman khusus.

File yang akan dicek nanti:
- app/Http/Controllers/Finance
- app/Models/Payment.php
- app/Models/OrderCost.php
- app/Services/Finance
- resources/views/finance

## PH-08 — HRD & Material

Belum ada halaman khusus.

File yang akan dicek nanti:
- app/Http/Controllers/Hrd
- app/Http/Controllers/Material
- app/Models/Employee.php
- app/Models/Attendance.php
- app/Models/Material.php
- app/Models/MaterialUsage.php
- app/Services/Hrd
- app/Services/Material
- resources/views/hrd
- resources/views/material

## PH-09 — Laporan & Hardening

Cek:
- Dashboard: /dashboard
- Admin dashboard: /admin/dashboard
- Realtime test: /broadcast-test

File utama:
- app/Http/Controllers/Report
- app/Services/Report
- resources/views/reports
- tests/Feature/Report

## Aturan cek cepat

1. Hanya cek file yang termasuk fase aktif.
2. Jangan baca seluruh repository.
3. Gunakan route sebagai titik awal.
4. Gunakan service sebagai sumber logika.
5. Jalankan test yang relevan, lalu full test di akhir.
6. Jangan ubah file di luar fase aktif.
