<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierOrderItem>
 */
class SupplierOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_order_id' => SupplierOrder::factory(),
            'product_id' => Product::factory(),
            'ordered_quantity' => 156,
            'confirmed_quantity' => null,
            'received_quantity' => null,
            'unit_price' => fake()->optional()->randomFloat(3, 1, 1000),
            'currency' => 'EUR',
            'status' => 'ordered',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
