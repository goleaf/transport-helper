<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\IntegrationConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationConnection>
 */
class IntegrationConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => fake()->randomElement(['erp', 'warehouse', 'google_sheets', 'accounting']),
            'name' => fake()->company(),
            'encrypted_config' => [
                'configured' => true,
            ],
            'is_active' => true,
            'last_sync_at' => null,
        ];
    }
}
