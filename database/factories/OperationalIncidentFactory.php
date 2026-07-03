<?php

namespace Database\Factories;

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentSlaStatus;
use App\Enums\IncidentSourceType;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\Company;
use App\Models\OperationalIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationalIncident>
 */
class OperationalIncidentFactory extends Factory
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
            'incident_number' => 'INC-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'incident_type' => IncidentType::Other,
            'severity' => IncidentSeverity::Medium,
            'priority' => IncidentPriority::P3,
            'status' => IncidentStatus::Open,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'source_type' => IncidentSourceType::Manual,
            'source_id' => null,
            'source_label' => 'Manual incident',
            'source_url' => null,
            'assigned_user_id' => null,
            'reported_by_user_id' => User::factory(),
            'first_response_at' => null,
            'response_due_at' => now()->addDay(),
            'resolution_due_at' => now()->addDays(3),
            'resolved_at' => null,
            'closed_at' => null,
            'sla_status' => IncidentSlaStatus::WithinSla,
            'root_cause_category' => null,
            'root_cause_summary' => null,
            'resolution_note' => null,
            'prevention_notes' => null,
            'corrective_action_required' => false,
            'no_action_required_reason' => null,
            'occurrence_count' => 1,
            'last_seen_at' => now(),
            'metadata_json' => [],
        ];
    }
}
