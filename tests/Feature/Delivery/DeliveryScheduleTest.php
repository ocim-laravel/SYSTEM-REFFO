<?php

namespace Tests\Feature\Delivery;

use App\Enums\DeliveryType;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\User;
use App\Services\Delivery\DeliveryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeliveryScheduleTest extends TestCase
{
    use DeliveryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_rejects_installation_when_production_is_not_finished(): void
    {
        $admin = $this->adminUser();
        $order = $this->finishedOrder(1, ItemStatus::Printing);
        $item = $order->items()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('deliveries.store'), $this->schedulePayload(
                $order,
                [$item->id],
                DeliveryType::Installation,
            ))
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('deliveries', 0);
        $this->assertSame(ItemStatus::Printing, $item->fresh()->status);
    }

    #[Test]
    public function it_charges_delivery_fee_once_for_three_item_installation(): void
    {
        $admin = $this->adminUser();
        $field = $this->fieldUser();
        $order = $this->finishedOrder(3, ItemStatus::Packed, '350000.00');
        $itemIds = $order->items()->pluck('id')->all();

        $this->actingAs($admin)
            ->post(route('deliveries.store'), $this->schedulePayload(
                $order,
                $itemIds,
                DeliveryType::Installation,
                [$field->id],
            ))
            ->assertRedirect();

        $delivery = Delivery::query()->firstOrFail();

        $this->assertSame(1, Delivery::query()->count());
        $this->assertSame('350000.00', (string) $delivery->fee);
        $this->assertSame(3, $delivery->items()->count());
        $this->assertSame('350000.00', app(DeliveryService::class)->chargedFee($order));
        $this->assertSame(OrderStatus::Installing, $order->fresh()->status);
    }

    #[Test]
    public function it_does_not_charge_fee_twice_for_later_schedules_of_same_order(): void
    {
        $admin = $this->adminUser();
        $order = $this->finishedOrder(4, ItemStatus::Packed, '125000.00');
        [$first, $second, $third, $fourth] = $order->items()->pluck('id')->all();
        $service = app(DeliveryService::class);

        $firstDelivery = $service->schedule(
            $this->schedulePayload($order, [$first, $second], DeliveryType::Delivery),
            $admin,
        );
        $secondDelivery = $service->schedule(
            $this->schedulePayload($order, [$third, $fourth], DeliveryType::Delivery),
            $admin,
        );

        $this->assertSame('125000.00', (string) $firstDelivery->fee);
        $this->assertSame('0.00', (string) $secondDelivery->fee);
        $this->assertSame('125000.00', $service->chargedFee($order));
        $this->assertSame(2, Delivery::query()->count());
    }

    #[Test]
    public function it_allows_production_admin_to_assign_field_crew(): void
    {
        $production = User::factory()->forRole(UserRole::Production)->create();
        $field = $this->fieldUser();
        $order = $this->finishedOrder(1, ItemStatus::Packed);

        $delivery = app(DeliveryService::class)->schedule(
            $this->schedulePayload($order, $order->items()->pluck('id')->all()),
            $this->adminUser(),
        );

        $this->actingAs($production)
            ->post(route('deliveries.crew', $delivery), ['crew_ids' => [$field->id]])
            ->assertRedirect(route('deliveries.show', $delivery));

        $this->assertSame([$field->id], $delivery->fresh()->crew);
    }

    #[Test]
    public function it_rejects_scheduling_an_item_that_is_already_scheduled(): void
    {
        $admin = $this->adminUser();
        $order = $this->finishedOrder(2, ItemStatus::Packed);
        $itemIds = $order->items()->pluck('id')->all();
        $service = app(DeliveryService::class);

        $service->schedule(
            $this->schedulePayload($order, [$itemIds[0]], DeliveryType::Delivery),
            $admin,
        );

        $this->actingAs($admin)
            ->post(route('deliveries.store'), $this->schedulePayload($order, [$itemIds[0]], DeliveryType::Delivery))
            ->assertSessionHasErrors('item_ids');

        $this->assertSame(1, Delivery::query()->count());
    }

    #[Test]
    public function it_forbids_field_user_from_creating_schedule(): void
    {
        $field = $this->fieldUser();
        $order = $this->finishedOrder(1, ItemStatus::Packed);

        $this->actingAs($field)
            ->post(route('deliveries.store'), $this->schedulePayload(
                $order,
                $order->items()->pluck('id')->all(),
            ))
            ->assertForbidden();

        $this->assertDatabaseCount('deliveries', 0);
    }

    #[Test]
    public function it_allows_pickup_schedule_without_field_crew(): void
    {
        $admin = $this->adminUser();
        $order = $this->finishedOrder(1, ItemStatus::Packed);

        $this->actingAs($admin)
            ->post(route('deliveries.store'), $this->schedulePayload(
                $order,
                $order->items()->pluck('id')->all(),
                DeliveryType::Pickup,
            ))
            ->assertRedirect();

        $delivery = Delivery::query()->firstOrFail();

        $this->assertSame(DeliveryType::Pickup, $delivery->type);
        $this->assertSame([], $delivery->crew);
    }
}
