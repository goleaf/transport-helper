<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'import_type' => 'sales_history',
            'source_type' => 'csv',
            'source_name' => fake()->optional()->word(),
            'adapter' => 'csv',
            'original_filename' => fake()->optional()->lexify('import-????.csv'),
            'checksum' => fake()->sha256(),
            'status' => 'completed',
            'total_rows' => 1,
            'successful_rows' => 1,
            'failed_rows' => 0,
            'started_by_user_id' => User::factory(),
            'started_at' => now(),
            'finished_at' => now(),
            'error_summary' => null,
        ];
    }
}
