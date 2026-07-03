<?php

namespace Database\Factories;

use App\Enums\EscalationStatus;
use App\Models\IncidentEscalation;
use App\Models\OperationalIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentEscalation>
 */
class IncidentEscalationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'operational_incident_id' => OperationalIncident::factory(),
            'escalation_level' => 1,
            'escalated_to_user_id' => User::factory(),
            'escalated_by_user_id' => User::factory(),
            'reason' => fake()->sentence(),
            'status' => EscalationStatus::Open,
            'escalated_at' => now(),
            'resolved_at' => null,
            'metadata_json' => [],
        ];
    }
}
