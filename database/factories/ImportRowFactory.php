<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRow>
 */
class ImportRowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'import_batch_id' => ImportBatch::factory(),
            'row_number' => fake()->numberBetween(1, 1000),
            'raw_json' => [
                'sku' => fake()->bothify('SKU-####'),
            ],
            'normalized_json' => [
                'sku' => fake()->bothify('SKU-####'),
            ],
            'status' => 'successful',
            'error_message' => null,
            'related_model_type' => null,
            'related_model_id' => null,
        ];
    }
}
