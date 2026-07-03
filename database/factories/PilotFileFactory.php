<?php

namespace Database\Factories;

use App\Enums\PilotFileType;
use App\Models\PilotFile;
use App\Models\PilotSupplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PilotFile>
 */
class PilotFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pilot_supplier_id' => PilotSupplier::factory(),
            'file_type' => fake()->randomElement(PilotFileType::values()),
            'original_filename' => fake()->lexify('sample-????.csv'),
            'stored_path' => fake()->lexify('pilot/1/sales_history_sample/sample-????.csv'),
            'mime_type' => 'text/csv',
            'size_bytes' => 128,
            'checksum' => hash('sha256', fake()->uuid()),
            'metadata_json' => [],
            'uploaded_by_user_id' => User::factory(),
        ];
    }
}
