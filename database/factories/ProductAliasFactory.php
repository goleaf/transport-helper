<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAlias>
 */
class ProductAliasFactory extends Factory
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
            'product_id' => Product::factory(),
            'alias' => 'ALIAS-'.$this->faker->unique()->numerify('####'),
            'alias_type' => 'sku_alias',
            'source_type' => 'manual',
            'source_reference' => null,
            'status' => 'active',
            'confidence' => 1.0,
            'reason' => 'Factory alias.',
            'approved_by_user_id' => User::factory(),
            'approved_at' => now(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
