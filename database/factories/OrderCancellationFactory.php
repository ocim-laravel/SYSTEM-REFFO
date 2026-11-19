<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderCancellation>
 */
class OrderCancellationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'reason' => fake()->sentence(),
            'actual_cost' => '0.00',
            'settlement_amount' => '0.00',
            'settlement_note' => null,
            'decided_by' => User::factory(),
            'decided_at' => fn () => now(),
        ];
    }
}
