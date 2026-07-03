<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierProductPrice>
 */
class SupplierProductPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'product_id' => Product::factory(),
            'currency' => 'EUR',
            'unit_price' => fake()->randomFloat(4, 1, 100),
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_to' => null,
            'source_type' => 'manual',
            'source_reference' => null,
            'status' => 'active',
            'created_by_user_id' => User::factory(),
        ];
    }
}
