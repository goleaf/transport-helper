<?php

namespace Database\Factories;

use App\Models\LogisticsOption;
use App\Models\SupplyOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogisticsOption>
 */
class LogisticsOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supply_order_id' => SupplyOrder::factory(),
            'carrier_name' => fake()->company(),
            'service_name' => fake()->optional()->word(),
            'price_cents' => fake()->numberBetween(20000, 90000),
            'currency' => 'EUR',
            'transit_days' => fake()->numberBetween(2, 10),
            'pickup_on' => now()->addDays(7)->toDateString(),
            'delivery_on' => now()->addDays(12)->toDateString(),
            'selected' => false,
        ];
    }
}
