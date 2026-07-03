<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockSnapshot>
 */
class StockSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'snapshot_date' => now()->toDateString(),
            'free_stock' => fake()->numberBetween(0, 500),
            'total_stock' => fake()->numberBetween(0, 600),
            'reserved_quantity' => fake()->numberBetween(0, 50),
            'damaged_quantity' => 0,
            'inactive_quantity' => 0,
            'in_transit_quantity' => fake()->numberBetween(0, 100),
            'source_type' => 'manual',
            'source_reference' => fake()->optional()->uuid(),
            'import_batch_id' => null,
        ];
    }
}
