<?php

namespace Database\Factories;

use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderProposal>
 */
class OrderProposalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'calculation_run_id' => CalculationRun::factory(),
            'supplier_id' => Supplier::factory(),
            'status' => 'draft',
            'total_lines' => 0,
            'created_by_user_id' => User::factory(),
            'approved_by_user_id' => null,
            'approved_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
