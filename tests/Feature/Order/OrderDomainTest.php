<?php

namespace Tests\Feature\Order;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\ItemStatusHistory;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Domain order/item (MOD-02, PH-02): tanpa controller, view, route, atau policy.
 */
class OrderDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function customer(User $actor, bool $isActive = true): Customer
    {
        return app(CustomerService::class)->create([
            'name' => 'Pelanggan Uji Order',
            'type' => CustomerType::Company->value,
            'is_active' => $isActive,
        ], $actor);
    }

    /**
     * @return array<string, mixed>
     */
    private function threeItemData(Customer $customer): array
    {
        return [
            'customer_id' => $customer->id,
            'discount' => 50000,
            'delivery_fee' => 25000,
            'items' => [
                [
                    'title' => 'Kartu Nama',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 2,
                    'unit' => 'box',
                    'unit_price' => 100000,
                ],
                [
                    'title' => 'Brosur',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 3,
                    'unit' => 'pcs',
                    'unit_price' => 50000,
                ],
                [
                    'title' => 'Spanduk',
                    'category' => ItemCategory::Reklame->value,
                    'qty' => 1,
                    'unit' => 'm2',
                    'unit_price' => 250000,
                ],
            ],
        ];
    }

    #[Test]
    public function it_calculates_the_total_of_a_three_item_order_when_a_discount_is_applied(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->customer($admin);

        $order = app(OrderService::class)->create($this->threeItemData($customer), $admin);

        $this->assertSame(3, $order->items()->count());
        $this->assertSame(sprintf('ORD-%d-0001', now()->year), $order->code);
        $this->assertSame(OrderStatus::PendingValidation, $order->status);
        $this->assertSame('600000.00', (string) $order->subtotal);
        $this->assertSame('50000.00', (string) $order->discount);
        $this->assertSame('25000.00', (string) $order->delivery_fee);
        $this->assertSame('0.00', (string) $order->tax);
        $this->assertSame('575000.00', (string) $order->total);
        $this->assertSame(
            ['200000.00', '150000.00', '250000.00'],
            $order->items()->orderBy('seq')->get()
                ->map(fn (OrderItem $item): string => (string) $item->total)->all(),
        );
        $this->assertFalse(Schema::hasColumn('order_items', 'delivery_fee'));
    }

    #[Test]
    public function it_does_not_change_another_item_when_one_item_status_changes(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->customer($admin);
        $order = app(OrderService::class)->create($this->threeItemData($customer), $admin);

        $itemA = $order->items()->orderBy('seq')->firstOrFail();
        $itemB = $order->items()->orderByDesc('seq')->firstOrFail();

        app(OrderService::class)->changeItemStatus(
            $itemA,
            ItemStatus::Designing,
            $admin,
            'Mulai dikerjakan desainer',
        );

        $this->assertSame(ItemStatus::Designing, $itemA->fresh()->status);
        $this->assertSame(ItemStatus::Draft, $itemB->fresh()->status);
        $this->assertSame(1, $itemA->statusHistories()->count());
        $this->assertSame(0, $itemB->statusHistories()->count());
        $this->assertDatabaseHas('item_status_histories', [
            'order_item_id' => $itemA->id,
            'from_status' => ItemStatus::Draft->value,
            'to_status' => ItemStatus::Designing->value,
            'changed_by' => $admin->id,
            'note' => 'Mulai dikerjakan desainer',
        ]);
    }

    #[Test]
    public function it_rejects_an_inactive_customer_when_creating_an_order(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->customer($admin, false);

        try {
            app(OrderService::class)->create($this->threeItemData($customer), $admin);
            $this->fail('Order untuk pelanggan nonaktif seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('customer_id', $exception->errors());
            $this->assertStringContainsString('nonaktif', $exception->errors()['customer_id'][0]);
        }

        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function it_deletes_an_item_when_the_order_is_still_draft(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->customer($admin);
        $order = app(OrderService::class)->create($this->threeItemData($customer), $admin);
        $item = $order->items()->orderByDesc('seq')->firstOrFail();

        app(OrderService::class)->deleteItem($item, $admin);

        $this->assertSame(2, $order->items()->count());
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
        $this->assertSame('325000.00', (string) $order->fresh()->total);
    }

    #[Test]
    public function it_refuses_to_delete_an_item_when_the_order_is_no_longer_draft(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->customer($admin);
        $order = app(OrderService::class)->create($this->threeItemData($customer), $admin);
        $item = $order->items()->firstOrFail();
        $order->update(['status' => OrderStatus::Validated]);

        try {
            app(OrderService::class)->deleteItem($item, $admin);
            $this->fail('Item order tervalidasi seharusnya tidak bisa dihapus.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('order', $exception->errors());
        }

        $this->assertDatabaseHas('order_items', ['id' => $item->id]);
    }

    #[Test]
    public function it_records_price_changes_with_before_and_after_when_an_item_price_is_updated(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->customer($admin);
        $order = app(OrderService::class)->create($this->threeItemData($customer), $admin);
        $item = $order->items()->orderBy('seq')->firstOrFail();

        app(OrderService::class)->updateItemPrice($item, 125000, $admin);

        $this->assertSame('125000.00', (string) $item->fresh()->unit_price);
        $this->assertSame('250000.00', (string) $item->fresh()->total);
        $this->assertSame('625000.00', (string) $order->fresh()->total);

        $log = AuditLog::query()->where('action', 'order_item.price_updated')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(OrderItem::class, $log->model_type);
        $this->assertSame($item->id, $log->model_id);
        $this->assertSame('100000.00', $log->before['unit_price']);
        $this->assertSame('125000.00', $log->after['unit_price']);
    }

    #[Test]
    public function it_builds_all_order_domain_models_when_factories_are_used(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->count(2), 'items')
            ->create();
        $history = ItemStatusHistory::factory()->create();
        $cancellation = OrderCancellation::factory()->create();

        $this->assertSame(OrderStatus::Draft, $order->status);
        $this->assertCount(2, $order->items);
        $this->assertTrue($order->customer()->exists());
        $this->assertTrue($order->creator()->exists());
        $this->assertTrue($order->cancellation()->doesntExist());
        $this->assertTrue($history->orderItem()->exists());
        $this->assertSame(ItemStatus::Draft, $history->from_status);
        $this->assertSame(ItemStatus::QueuedDesign, $history->to_status);
        $this->assertTrue($cancellation->order()->exists());
        $this->assertTrue($cancellation->decidedBy()->exists());
    }
}
