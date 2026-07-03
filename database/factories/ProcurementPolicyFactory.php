<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ProcurementPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementPolicy>
 */
class ProcurementPolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'status' => 'active',
            'enforcement_mode' => 'advisory',
            'default_currency' => 'EUR',
            'rules_json' => [],
            'approval_thresholds_json' => [],
            'supplier_rules_json' => [],
            'budget_rules_json' => [],
            'is_default' => false,
            'created_by_user_id' => User::factory(),
            'updated_by_user_id' => null,
        ];
    }
}
