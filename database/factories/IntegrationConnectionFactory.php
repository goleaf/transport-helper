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
            'provider' => 'manual',
            'environment' => 'test',
            'encrypted_config' => [
                'configured' => true,
            ],
            'is_external' => false,
            'requires_approval' => true,
            'status' => 'draft',
            'approval_status' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'last_tested_at' => null,
            'last_test_status' => null,
            'last_test_result_json' => null,
            'is_active' => true,
            'last_sync_at' => null,
            'notes' => null,
        ];
    }
}
