<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\Priority;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Order\OrderService;
use Illuminate\Database\Seeder;

/**
 * Data demo gate PH-02: 1 pelanggan + 1 order 4 item (printing & reklame).
 *
 * Idempoten dan hanya untuk local/testing — aman dijalankan ulang, tidak
 * pernah menulis data contoh ke production. Order dibuat lewat OrderService
 * agar kode, audit (BR-17), dan total mengikuti aturan MOD-02 yang sama.
 */
class DemoGatePh02Seeder extends Seeder
{
    private const CUSTOMER_NAME = 'PT Demo Gate PH-02';

    private const ORDER_NOTES = 'Data demo gate PH-02 — 4 item (printing & reklame).';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('DemoGatePh02Seeder: dilewati (bukan environment local/testing).');

            return;
        }

        /** @var User|null $admin */
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', UserRole::Admin->value))
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            $this->command?->error('DemoGatePh02Seeder: user Admin belum ada — jalankan RolePermissionSeeder & UserSeeder dulu.');

            return;
        }

        $customer = Customer::query()->where('name', self::CUSTOMER_NAME)->first()
            ?? app(CustomerService::class)->create([
                'name' => self::CUSTOMER_NAME,
                'type' => CustomerType::Company->value,
                'phone' => '081299988877',
                'address' => 'Jl. Demo Gate No. 1, Bandung',
            ], $admin);

        $exists = Order::query()
            ->where('customer_id', $customer->id)
            ->where('notes', self::ORDER_NOTES)
            ->exists();

        if ($exists) {
            $this->command?->info('DemoGatePh02Seeder: data sudah ada, dilewati.');

            return;
        }

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'discount' => 50000,
            'delivery_fee' => 30000,
            'notes' => self::ORDER_NOTES,
            'items' => [
                [
                    'title' => 'Kartu Nama Premium',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 2,
                    'unit' => 'box',
                    'unit_price' => 150000,
                    'priority' => Priority::Normal->value,
                ],
                [
                    'title' => 'Brosur A5',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 500,
                    'unit' => 'pcs',
                    'unit_price' => 2500,
                    'priority' => Priority::H1->value,
                ],
                [
                    'title' => 'Spanduk Reklame',
                    'category' => ItemCategory::Reklame->value,
                    'qty' => 5,
                    'unit' => 'm2',
                    'unit_price' => 80000,
                    'priority' => Priority::Urgent->value,
                ],
                [
                    'title' => 'Sticker Vinyl',
                    'category' => ItemCategory::Reklame->value,
                    'qty' => 10,
                    'unit' => 'm2',
                    'unit_price' => 45000,
                    'priority' => Priority::Normal->value,
                ],
            ],
        ], $admin);

        $this->command?->info(sprintf(
            'DemoGatePh02Seeder: order %s dibuat (subtotal %s, diskon %s, kirim %s, total %s).',
            $order->code,
            $order->subtotal,
            $order->discount,
            $order->delivery_fee,
            $order->total,
        ));
    }
}
