<?php

namespace Tests\Feature\Delivery;

use App\Enums\DeliveryType;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

/**
 * Helper bersama untuk menyiapkan order yang sudah selesai produksi (PH-05).
 */
trait DeliveryTestHelpers
{
    protected function adminUser(): User
    {
        return User::factory()->forRole(UserRole::Admin)->create();
    }

    protected function fieldUser(): User
    {
        return User::factory()->forRole(UserRole::Field)->create();
    }

    /**
     * @param  list<ItemStatus>|ItemStatus  $statuses
     */
    protected function finishedOrder(int $itemCount = 3, ItemStatus|array $statuses = ItemStatus::Packed, string $deliveryFee = '250000.00'): Order
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'status' => OrderStatus::Finished,
            'delivery_fee' => $deliveryFee,
        ]);

        $list = is_array($statuses) ? $statuses : array_fill(0, $itemCount, $statuses);

        foreach ($list as $index => $status) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'seq' => $index + 1,
                'title' => 'Item Reklame '.($index + 1),
                'status' => $status,
                'deadline_at' => now()->addDays(7)->toDateTimeString(),
            ]);
        }

        return $order;
    }

    /**
     * @param  list<int>  $itemIds
     * @param  list<int>  $crewIds
     * @return array<string, mixed>
     */
    protected function schedulePayload(
        Order $order,
        array $itemIds,
        DeliveryType|string $type = DeliveryType::Installation,
        array $crewIds = [],
        array $overrides = [],
    ): array {
        return array_merge([
            'order_id' => $order->id,
            'item_ids' => $itemIds,
            'type' => $type instanceof DeliveryType ? $type->value : $type,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'address' => 'Jl. Contoh No. 1',
            'crew_ids' => $crewIds,
        ], $overrides);
    }
}
