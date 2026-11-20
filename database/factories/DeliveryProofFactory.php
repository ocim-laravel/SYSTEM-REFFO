<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\DeliveryProof;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryProof>
 */
class DeliveryProofFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'photo_path' => 'delivery-proofs/'.fake()->uuid().'.jpg',
            'note' => null,
            'uploaded_by' => null,
            'uploaded_at' => now(),
            'confirmed_by' => null,
            'confirmed_at' => null,
        ];
    }
}
