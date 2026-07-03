<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SalesExclusionRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesExclusionRule>
 */
class SalesExclusionRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => null,
            'product_id' => null,
            'category' => null,
            'rule_type' => 'manual_exclusion',
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
            'applies_to' => 'all_calculation_periods',
            'reason' => fake()->sentence(),
            'is_active' => true,
            'created_by_user_id' => User::factory(),
            'approved_by_user_id' => null,
            'approved_at' => null,
        ];
    }
}
