<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ProcurementBudget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementBudget>
 */
class ProcurementBudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'period_type' => 'monthly',
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
            'currency' => 'EUR',
            'total_amount' => 10000,
            'status' => 'active',
            'owner_user_id' => User::factory(),
            'notes' => null,
            'created_by_user_id' => User::factory(),
        ];
    }
}
