<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ExportFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportFile>
 */
class ExportFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'export_type' => 'csv',
            'related_model_type' => null,
            'related_model_id' => null,
            'filename' => fake()->lexify('export-????.csv'),
            'stored_path' => fake()->lexify('exports/????.csv'),
            'mime_type' => 'text/csv',
            'status' => 'ready',
            'created_by_user_id' => User::factory(),
        ];
    }
}
