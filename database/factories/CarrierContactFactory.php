<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\CarrierContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarrierContact>
 */
class CarrierContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'carrier_id' => Carrier::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
