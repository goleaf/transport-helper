<?php

namespace Database\Factories;

use App\Models\OperationalIncident;
use App\Models\OperationalIncidentEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationalIncidentEvent>
 */
class OperationalIncidentEventFactory extends Factory
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
            'event_type' => 'incident_created',
            'old_values_json' => null,
            'new_values_json' => [],
            'metadata_json' => [],
            'created_by_user_id' => User::factory(),
            'created_at' => now(),
        ];
    }
}
