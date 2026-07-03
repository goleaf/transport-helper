<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ReportRun;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportRun>
 */
class ReportRunFactory extends Factory
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
            'saved_report_id' => SavedReport::factory(),
            'report_type' => 'supplier_performance',
            'status' => 'completed',
            'filters_json' => ['report_period' => 'last_30_days'],
            'result_summary_json' => ['total' => 1],
            'warnings_json' => [],
            'errors_json' => [],
            'started_by_user_id' => User::factory(),
            'started_at' => now(),
            'finished_at' => now(),
        ];
    }
}
