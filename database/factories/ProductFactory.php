<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'manufacturer_sku' => fake()->optional()->bothify('MFG-####'),
            'name' => fake()->words(3, true),
            'category' => fake()->optional()->word(),
            'brand' => fake()->optional()->company(),
            'unit' => 'pcs',
            'is_active' => true,
        ];
    }
}
