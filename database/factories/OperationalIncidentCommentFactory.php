<?php

namespace Database\Factories;

use App\Models\OperationalIncident;
use App\Models\OperationalIncidentComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationalIncidentComment>
 */
class OperationalIncidentCommentFactory extends Factory
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
            'user_id' => User::factory(),
            'comment' => fake()->sentence(),
            'is_internal' => true,
            'metadata_json' => [],
        ];
    }
}
