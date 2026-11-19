<?php

namespace Tests\Feature\Order;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AC-02-5 — pembatalan order (MOD-02, PH-02): biaya aktual dan nilai
 * kesepakatan tercatat terpisah, alasan wajib, audit before/after (BR-15, BR-17).
 */
class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function customer(User $actor): Customer
    {
        return app(CustomerService::class)->create([
            'name' => 'PT Pelanggan Batal',
            'type' => CustomerType::Company->value,
            'is_active' => true,
        ], $actor);
    }

    private function order(User $actor, int $items = 2): Order
    {
        $payload = ['customer_id' => $this->customer($actor)->id, 'items' => []];

        for ($i = 1; $i <= $items; $i++) {
            $payload['items'][] = [
                'title' => 'Item '.$i,
                'category' => ItemCategory::Printing->value,
                'qty' => 1,
                'unit' => 'pcs',
                'unit_price' => 10000,
            ];
        }

        return app(OrderService::class)->create($payload, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        string $reason = 'Pelanggan membatalkan karena perubahan kebutuhan.',
        string $actualCost = '150000.50',
        string $settlement = '75000.25',
    ): array {
        return [
            'reason' => $reason,
            'actual_cost' => $actualCost,
            'settlement_amount' => $settlement,
            'settlement_note' => 'Disepakati setelah diskusi dengan Owner.',
        ];
    }

    #[Test]
    public function it_records_actual_cost_and_settlement_separately_when_admin_cancels_an_order(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin);

        $response = $this->actingAs($admin)
            ->from(route('orders.show', $order))
            ->post(route('orders.cancel.store', $order), $this->payload());

        $response->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $order->id,
            'reason' => 'Pelanggan membatalkan karena perubahan kebutuhan.',
            'actual_cost' => '150000.50',
            'settlement_amount' => '75000.25',
            'settlement_note' => 'Disepakati setelah diskusi dengan Owner.',
            'decided_by' => $admin->id,
        ]);

        $cancellation = $order->fresh()->cancellation;
        $this->assertNotNull($cancellation->decided_at);
        $this->assertSame('150000.50', (string) $cancellation->actual_cost);
        $this->assertSame('75000.25', (string) $cancellation->settlement_amount);
        $this->assertNotSame($cancellation->actual_cost, $cancellation->settlement_amount);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    #[Test]
    public function it_writes_an_audit_log_with_before_after_and_reason_when_order_is_cancelled(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin);
        $statusBefore = $order->status;

        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $this->payload())
            ->assertRedirect();

        $audit = AuditLog::query()->where('action', 'order.cancelled')->firstOrFail();

        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame(Order::class, $audit->model_type);
        $this->assertSame($order->id, $audit->model_id);
        $this->assertSame('Pelanggan membatalkan karena perubahan kebutuhan.', $audit->reason);
        $this->assertSame($statusBefore->value, $audit->before['status']);
        $this->assertSame(OrderStatus::Cancelled->value, $audit->after['status']);
    }

    #[Test]
    public function it_marks_all_items_cancelled_with_history_when_order_is_cancelled(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin, 3);
        app(OrderService::class)->validate($order, $admin);

        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $this->payload())
            ->assertRedirect();

        $order->refresh()->load('items');
        $this->assertCount(3, $order->items);
        $this->assertTrue($order->items->every(fn ($item) => $item->status === ItemStatus::Cancelled));
        $this->assertSame(3, $order->items->sum(fn ($item) => $item->statusHistories()->where('to_status', ItemStatus::Cancelled->value)->count()));
    }

    #[Test]
    public function it_allows_owner_to_cancel_an_order(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $owner = User::factory()->forRole(UserRole::Owner)->create();
        $order = $this->order($admin);

        $this->actingAs($owner)->post(route('orders.cancel.store', $order), $this->payload())
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame($owner->id, (int) $order->fresh()->cancellation->decided_by);
    }

    #[Test]
    public function it_forbids_designer_from_cancelling_an_order(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $order = $this->order($admin);

        $this->actingAs($designer)->get(route('orders.cancel', $order))->assertForbidden();
        $this->actingAs($designer)->post(route('orders.cancel.store', $order), $this->payload())->assertForbidden();

        $this->assertSame(OrderStatus::Draft, $order->fresh()->status);
        $this->assertDatabaseCount('order_cancellations', 0);
    }

    #[Test]
    public function it_rejects_cancellation_when_reason_is_missing_or_blank(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin);

        $payload = $this->payload();
        unset($payload['reason']);
        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $payload)
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $this->payload(reason: '     '))
            ->assertSessionHasErrors('reason');

        $this->assertSame(OrderStatus::Draft, $order->fresh()->status);
        $this->assertDatabaseCount('order_cancellations', 0);
    }

    #[Test]
    public function it_rejects_cancellation_when_cost_or_settlement_is_negative(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin);

        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $this->payload(actualCost: '-1'))
            ->assertSessionHasErrors('actual_cost');
        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $this->payload(settlement: '-1'))
            ->assertSessionHasErrors('settlement_amount');

        $this->assertDatabaseCount('order_cancellations', 0);
    }

    #[Test]
    public function it_rejects_a_second_cancellation_when_order_is_already_cancelled(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin);

        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $this->payload())
            ->assertRedirect();
        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $this->payload())
            ->assertSessionHasErrors('order');

        $this->assertDatabaseCount('order_cancellations', 1);
    }

    #[Test]
    public function it_shows_the_cancellation_form_and_record_to_authorized_users(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $owner = User::factory()->forRole(UserRole::Owner)->create();
        $order = $this->order($admin);

        $this->actingAs($admin)->get(route('orders.cancel', $order))
            ->assertOk()
            ->assertSee('Biaya aktual')
            ->assertSee('Nilai kesepakatan');
        $this->actingAs($owner)->get(route('orders.cancel', $order))->assertOk();

        $this->actingAs($admin)->post(route('orders.cancel.store', $order), $this->payload())->assertRedirect();

        $this->actingAs($admin)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Pembatalan')
            ->assertSee('Pelanggan membatalkan karena perubahan kebutuhan.')
            ->assertSee('150.000,50')
            ->assertSee('75.000,25');
    }
}
