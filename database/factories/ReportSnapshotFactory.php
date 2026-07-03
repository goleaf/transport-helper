<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ReportSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportSnapshot>
 */
class ReportSnapshotFactory extends Factory
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
            'report_type' => 'management_dashboard',
            'snapshot_date' => now()->toDateString(),
            'metrics_json' => ['open_supplier_orders' => 0],
            'filters_json' => ['report_period' => 'last_30_days'],
            'created_by_user_id' => User::factory(),
        ];
    }
}
