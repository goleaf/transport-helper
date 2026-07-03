<?php

namespace Database\Factories;

use App\Enums\IncidentSeverity;
use App\Models\Company;
use App\Models\IncidentSlaPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentSlaPolicy>
 */
class IncidentSlaPolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'incident_type' => null,
            'severity' => IncidentSeverity::Medium->value,
            'priority' => null,
            'response_minutes' => 1440,
            'resolution_minutes' => 4320,
            'escalation_minutes' => null,
            'is_active' => true,
            'created_by_user_id' => User::factory(),
        ];
    }
}
