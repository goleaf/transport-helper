<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carrier>
 */
class CarrierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->company(),
            'code' => fake()->optional()->bothify('CAR-###'),
            'default_currency' => 'EUR',
            'reliability_score' => fake()->randomFloat(2, 70, 99),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
