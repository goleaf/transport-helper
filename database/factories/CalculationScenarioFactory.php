<?php

namespace Database\Factories;

use App\Models\CalculationScenario;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalculationScenario>
 */
class CalculationScenarioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => null,
            'base_calculation_run_id' => null,
            'name' => fake()->words(3, true),
            'status' => 'draft',
            'simulation_mode' => 'supplier',
            'formula_version' => 'v1_scenario',
            'parameters_json' => [],
            'profile_snapshot_json' => null,
            'summary_json' => null,
            'warnings_json' => [],
            'errors_json' => [],
            'created_by_user_id' => User::factory(),
            'simulated_at' => null,
            'converted_order_proposal_id' => null,
        ];
    }
}
