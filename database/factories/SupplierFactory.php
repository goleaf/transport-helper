<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->company(),
            'code' => fake()->optional()->bothify('SUP-###'),
            'type' => fake()->randomElement(['manufacturer', 'distributor', 'carrier', 'mixed']),
            'default_language' => fake()->optional()->randomElement(['en', 'de', 'lt', 'pl']),
            'default_currency' => 'EUR',
            'default_lead_time_days' => fake()->numberBetween(7, 45),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
