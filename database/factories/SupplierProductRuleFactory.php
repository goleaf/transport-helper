<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierProductRule>
 */
class SupplierProductRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'product_id' => Product::factory(),
            'supplier_sku' => fake()->optional()->bothify('SSKU-####'),
            'moq' => 1,
            'pack_multiple' => 6,
            'pallet_quantity' => 156,
            'min_transport_quantity' => null,
            'lead_time_days' => 21,
            'safety_days' => 7,
            'safety_rule_type' => 'days',
            'transport_rule_type' => 'standard',
            'order_enabled' => true,
        ];
    }
}
