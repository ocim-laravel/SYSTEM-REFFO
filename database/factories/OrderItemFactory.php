<?php

namespace Database\Factories;

use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\Priority;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'seq' => 1,
            'title' => fake()->words(3, true),
            'category' => ItemCategory::Printing,
            'specification' => null,
            'qty' => '1.00',
            'unit' => 'pcs',
            'unit_price' => '0.00',
            'total' => '0.00',
            'deadline_at' => null,
            'priority' => Priority::Normal,
            'status' => ItemStatus::Draft,
        ];
    }
}
