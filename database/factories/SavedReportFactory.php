<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedReport>
 */
class SavedReportFactory extends Factory
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
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'report_type' => 'supplier_performance',
            'filters_json' => ['report_period' => 'last_30_days'],
            'columns_json' => null,
            'chart_config_json' => null,
            'is_shared' => false,
            'is_default' => false,
            'created_by_user_id' => User::factory(),
        ];
    }
}
