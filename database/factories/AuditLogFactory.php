<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'event_type' => 'procurement.event',
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values_json' => null,
            'new_values_json' => [
                'status' => 'created',
            ],
            'metadata_json' => [],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
            'created_at' => now(),
        ];
    }
}
