<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductIdentity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierProductIdentity>
 */
class SupplierProductIdentityFactory extends Factory
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
            'supplier_id' => Supplier::factory(),
            'product_id' => Product::factory(),
            'supplier_sku' => 'SUP-'.$this->faker->unique()->numerify('#####'),
            'manufacturer_sku' => 'MFG-'.$this->faker->unique()->numerify('#####'),
            'supplier_product_name' => $this->faker->words(3, true),
            'barcode' => null,
            'source_type' => 'manual',
            'source_reference' => null,
            'status' => 'active',
            'confidence' => 1.0,
            'reason' => 'Factory supplier product mapping.',
            'approved_by_user_id' => User::factory(),
            'approved_at' => now(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
