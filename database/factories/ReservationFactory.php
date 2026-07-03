<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'project_name' => fake()->optional()->words(2, true),
            'customer_name' => fake()->optional()->company(),
            'manager_name' => fake()->optional()->name(),
            'reserved_at' => now()->toDateString(),
            'expected_usage_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
            'source_type' => 'manual',
            'source_reference' => fake()->optional()->uuid(),
        ];
    }
}
