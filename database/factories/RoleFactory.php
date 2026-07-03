<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'admin',
                'supply_manager',
                'logistics_manager',
                'accountant',
                'viewer',
            ]).'-'.fake()->unique()->numberBetween(1000, 9999),
        ];
    }
}
