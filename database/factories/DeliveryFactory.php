<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'type' => DeliveryType::Delivery,
            'scheduled_at' => now()->addDay(),
            'scheduled_by' => null,
            'crew' => null,
            'status' => DeliveryStatus::Scheduled,
            'address' => null,
            'fee' => '0.00',
            'departed_at' => null,
            'arrived_at' => null,
            'completed_by' => null,
            'completed_at' => null,
            'notes' => null,
        ];
    }
}
