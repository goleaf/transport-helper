<?php

namespace Database\Factories;

use App\Enums\CorrectiveActionStatus;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentCorrectiveAction>
 */
class IncidentCorrectiveActionFactory extends Factory
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
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'owner_user_id' => User::factory(),
            'due_date' => now()->addWeek()->toDateString(),
            'status' => CorrectiveActionStatus::Open,
            'completion_note' => null,
            'completed_at' => null,
            'verified_by_user_id' => null,
            'verified_at' => null,
        ];
    }
}
