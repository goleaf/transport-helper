<?php

namespace Database\Factories;

use App\Models\ProcurementBudget;
use App\Models\ProcurementBudgetLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementBudgetLine>
 */
class ProcurementBudgetLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'procurement_budget_id' => ProcurementBudget::factory(),
            'supplier_id' => null,
            'product_id' => null,
            'category' => null,
            'project_name' => null,
            'manager_name' => null,
            'amount' => 5000,
            'committed_amount' => null,
            'spent_amount' => null,
            'metadata_json' => [],
        ];
    }
}
