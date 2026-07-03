<?php

namespace Database\Factories;

use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalculationRun>
 */
class CalculationRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'calculation_date' => now()->toDateString(),
            'formula_version' => 't0-t1-t2-t3-v1',
            'parameters_json' => [
                'reserve_percent' => 4,
            ],
            'status' => 'completed',
            'started_by_user_id' => User::factory(),
            'started_at' => now(),
            'finished_at' => now(),
        ];
    }
}
