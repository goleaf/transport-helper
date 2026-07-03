<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->optional()->bothify('CMP-###'),
            'timezone' => 'Europe/Vilnius',
            'default_currency' => 'EUR',
        ];
    }
}
