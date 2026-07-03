<?php

namespace Database\Factories;

use App\Enums\SupplyOrderStatus;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupplyOrder>
 */
class SupplyOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'SO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'manufacturer_id' => Manufacturer::factory(),
            'product_id' => Product::factory(),
            'created_by_id' => User::factory(),
            'status' => SupplyOrderStatus::Draft,
            'customer_reference' => fake()->optional()->bothify('CUSTOMER-####'),
            'requested_quantity' => 150,
            'available_quantity' => 0,
            'required_quantity' => 150,
            'manufacturer_quantity' => 156,
            'reserve_percent' => 4,
        ];
    }
}
