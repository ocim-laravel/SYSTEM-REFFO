<?php

namespace Tests\Feature\Order;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AC-02-7 (BR-05): item yang sudah ACC dikunci dari mutasi sampai ada
 * Change Request yang disetujui (MOD-03). Domain service saja, tanpa HTTP/UI.
 */
class AccItemGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function orderWithItems(User $admin, ItemStatus ...$statuses): Order
    {
        $customer = app(CustomerService::class)->create([
            'name' => 'Pelanggan Guard ACC',
            'type' => CustomerType::Company->value,
        ], $admin);

        $items = [];
        foreach ($statuses as $index => $status) {
            $items[] = [
                'title' => 'Item '.($index + 1),
                'category' => ItemCategory::Printing->value,
                'qty' => 1,
                'unit' => 'pcs',
                'unit_price' => 100000,
            ];
        }

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => $items,
        ], $admin);

        // Fixture: menaruh item pada status ACC tanpa menulis riwayat, agar
        // jumlah histories bisa dipakai sebagai bukti integritas #7.
        foreach ($order->items()->orderBy('seq')->get() as $index => $item) {
            if ($statuses[$index] !== ItemStatus::Draft) {
                $item->update(['status' => $statuses[$index]]);
            }
        }

        return $order->refresh();
    }

    private function firstItem(Order $order): OrderItem
    {
        return $order->items()->orderBy('seq')->firstOrFail();
    }

    #[Test]
    public function it_rejects_a_price_update_when_an_item_is_acc(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->orderWithItems($admin, ItemStatus::Acc, ItemStatus::Draft);
        $item = $this->firstItem($order);
        $price = (string) $item->unit_price;
        $total = (string) $order->total;

        try {
            app(OrderService::class)->updateItemPrice($item, 250000, $admin);
            $this->fail('Perubahan harga item ACC seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('item', $exception->errors());
            $this->assertStringContainsString('ACC', $exception->errors()['item'][0]);
        }

        $this->assertSame($price, (string) $item->fresh()->unit_price);
        $this->assertSame($total, (string) $order->fresh()->total);
        $this->assertTrue(AuditLog::query()->where('action', 'order_item.price_updated')->doesntExist());
    }

    #[Test]
    public function it_rejects_deletion_when_an_item_is_acc(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->orderWithItems($admin, ItemStatus::Acc, ItemStatus::Draft);
        $item = $this->firstItem($order);

        try {
            app(OrderService::class)->deleteItem($item, $admin);
            $this->fail('Penghapusan item ACC seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('item', $exception->errors());
            $this->assertStringContainsString('ACC', $exception->errors()['item'][0]);
        }

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'status' => ItemStatus::Acc->value,
        ]);
        $this->assertTrue(AuditLog::query()->where('action', 'order_item.deleted')->doesntExist());
    }

    #[Test]
    public function it_rejects_a_status_change_when_an_item_is_acc(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->orderWithItems($admin, ItemStatus::Acc, ItemStatus::Draft);
        $item = $this->firstItem($order);

        try {
            app(OrderService::class)->changeItemStatus($item, ItemStatus::QueuedProduction, $admin);
            $this->fail('Perubahan status item ACC seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('item', $exception->errors());
            $this->assertStringContainsString('ACC', $exception->errors()['item'][0]);
        }

        $this->assertSame(ItemStatus::Acc, $item->fresh()->status);
        $this->assertSame(0, $item->statusHistories()->count());
    }

    #[Test]
    public function it_still_allows_mutations_when_items_are_draft_or_queued_design(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->orderWithItems($admin, ItemStatus::Draft, ItemStatus::QueuedDesign);
        $draft = $this->firstItem($order);
        $queued = $order->items()->orderByDesc('seq')->firstOrFail();

        $service = app(OrderService::class);
        $service->updateItemPrice($draft, 175000, $admin);
        $this->assertSame('175000.00', (string) $draft->fresh()->unit_price);

        $service->changeItemStatus($queued, ItemStatus::Designing, $admin);
        $this->assertSame(ItemStatus::Designing, $queued->fresh()->status);

        $service->deleteItem($draft, $admin);
        $this->assertDatabaseMissing('order_items', ['id' => $draft->id]);
        $this->assertSame(1, $queued->statusHistories()->count());
        $this->assertTrue(AuditLog::query()->where('action', 'order_item.price_updated')->exists());
    }

    #[Test]
    public function it_allows_a_no_op_status_change_when_an_item_is_acc(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->orderWithItems($admin, ItemStatus::Acc);
        $item = $this->firstItem($order);

        $result = app(OrderService::class)->changeItemStatus($item, ItemStatus::Acc, $admin);

        $this->assertSame(ItemStatus::Acc, $result->status);
        $this->assertSame(0, $item->statusHistories()->count());
    }

    #[Test]
    public function it_does_not_write_status_history_when_an_acc_mutation_is_rejected(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->orderWithItems($admin, ItemStatus::Acc, ItemStatus::Draft);
        $item = $this->firstItem($order);
        $this->assertSame(0, $item->statusHistories()->count());

        $attempts = [
            fn () => app(OrderService::class)->changeItemStatus($item, ItemStatus::Designing, $admin),
            fn () => app(OrderService::class)->updateItemPrice($item, 999000, $admin),
            fn () => app(OrderService::class)->deleteItem($item, $admin),
        ];

        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('Mutasi item ACC seharusnya ditolak sebelum menulis apa pun.');
            } catch (ValidationException) {
                // Guard menolak sebelum transaksi/riwayat dimulai.
            }
        }

        $this->assertSame(ItemStatus::Acc, $item->fresh()->status);
        $this->assertSame(0, $item->statusHistories()->count());
        $this->assertDatabaseCount('item_status_histories', 0);
    }
}
