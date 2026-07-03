<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SupplierConfirmation;
use App\Models\SupplierConfirmationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierConfirmationItem>
 */
class SupplierConfirmationItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_confirmation_id' => SupplierConfirmation::factory(),
            'product_id' => Product::factory(),
            'ordered_quantity' => 156,
            'confirmed_quantity' => 156,
            'discrepancy_quantity' => 0,
            'status' => 'matched',
            'notes' => null,
        ];
    }
}
