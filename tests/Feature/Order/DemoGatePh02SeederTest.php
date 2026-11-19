<?php

namespace Tests\Feature\Order;

use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\DemoGatePh02Seeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Data demo gate PH-02: pelanggan + order 4 item campur printing/reklame.
 */
class DemoGatePh02SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_seeds_the_four_item_gate_order_once_when_run_repeatedly(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->seed(DemoGatePh02Seeder::class);

        $customer = Customer::query()->where('name', 'PT Demo Gate PH-02')->firstOrFail();
        $this->assertSame('081299988877', $customer->phone);
        $this->assertSame('Jl. Demo Gate No. 1, Bandung', $customer->address);

        $order = Order::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame(OrderStatus::Draft, $order->status);
        $this->assertSame($admin->id, $order->created_by);
        $this->assertSame('50000.00', (string) $order->discount);
        $this->assertSame('30000.00', (string) $order->delivery_fee);
        $this->assertSame('2400000.00', (string) $order->subtotal);
        $this->assertSame('2380000.00', (string) $order->total);

        $items = $order->items()->orderBy('seq')->get();
        $this->assertCount(4, $items);
        $this->assertSame([
            ['Kartu Nama Premium', 'printing', '2.00', 'box', '150000.00', 'normal', '300000.00'],
            ['Brosur A5', 'printing', '500.00', 'pcs', '2500.00', 'h1', '1250000.00'],
            ['Spanduk Reklame', 'reklame', '5.00', 'm2', '80000.00', 'urgent', '400000.00'],
            ['Sticker Vinyl', 'reklame', '10.00', 'm2', '45000.00', 'normal', '450000.00'],
        ], $items->map(fn (OrderItem $item): array => [
            $item->title,
            $item->category->value,
            (string) $item->qty,
            $item->unit,
            (string) $item->unit_price,
            $item->priority->value,
            (string) $item->total,
        ])->all());
        $this->assertTrue($items->every(fn (OrderItem $item): bool => $item->status === ItemStatus::Draft));

        $code = $order->code;

        $this->seed(DemoGatePh02Seeder::class);

        $this->assertSame(1, Customer::query()->where('name', 'PT Demo Gate PH-02')->count());
        $this->assertSame(1, Order::query()->where('customer_id', $customer->id)->count());
        $this->assertSame($code, Order::query()->where('customer_id', $customer->id)->value('code'));
        $this->assertSame(4, OrderItem::query()->where('order_id', $order->id)->count());
    }
}
