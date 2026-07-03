<?php

namespace Database\Factories;

use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboundOrderItem>
 */
class InboundOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inbound_order_id' => InboundOrder::factory(),
            'product_id' => Product::factory(),
            'ordered_quantity' => fake()->numberBetween(1, 200),
            'confirmed_quantity' => null,
            'received_quantity' => null,
            'expected_arrival_date' => now()->addDays(14)->toDateString(),
            'confirmed_arrival_date' => null,
            'status' => 'open',
        ];
    }
}
