<?php

namespace Database\Factories;

use App\Enums\LogisticsStatus;
use App\Models\LogisticsEntry;
use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogisticsEntry>
 */
class LogisticsEntryFactory extends Factory
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
            'logistics_option_id' => null,
            'updated_by_id' => User::factory(),
            'carrier_name' => fake()->company(),
            'price_cents' => fake()->numberBetween(20000, 90000),
            'currency' => 'EUR',
            'pickup_on' => now()->addDays(7)->toDateString(),
            'delivery_on' => now()->addDays(12)->toDateString(),
            'status' => LogisticsStatus::Planned,
            'compared_at' => now(),
        ];
    }
}
