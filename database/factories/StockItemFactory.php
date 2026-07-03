<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockItem>
 */
class StockItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'available_quantity' => fake()->numberBetween(0, 50),
            'incoming_quantity' => fake()->numberBetween(0, 20),
            'reserved_quantity' => fake()->numberBetween(0, 10),
        ];
    }
}
