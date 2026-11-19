<?php

namespace Database\Factories;

use App\Enums\ItemStatus;
use App\Models\ItemStatusHistory;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemStatusHistory>
 */
class ItemStatusHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'from_status' => ItemStatus::Draft,
            'to_status' => ItemStatus::QueuedDesign,
            'changed_by' => User::factory(),
            'note' => null,
        ];
    }
}
