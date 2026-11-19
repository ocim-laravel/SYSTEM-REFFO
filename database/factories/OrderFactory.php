<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Enums\OrderStatus;
use App\Enums\Priority;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\System\NumberGeneratorService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fn (): string => app(NumberGeneratorService::class)->nextOrder(),
            'customer_id' => fn (): int => Customer::query()->create([
                'code' => 'CUS-TEST-'.Str::upper(Str::random(8)),
                'name' => fake()->company(),
                'type' => CustomerType::Company->value,
                'is_active' => true,
            ])->id,
            'created_by' => User::factory(),
            'status' => OrderStatus::Draft,
            'priority' => Priority::Normal,
            'deadline_at' => null,
            'subtotal' => '0.00',
            'discount' => '0.00',
            'tax' => '0.00',
            'total' => '0.00',
            'delivery_fee' => '0.00',
            'notes' => null,
        ];
    }
}
