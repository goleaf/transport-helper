<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\InboundOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboundOrder>
 */
class InboundOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => Supplier::factory(),
            'supplier_order_id' => null,
            'order_number' => fake()->optional()->bothify('IN-####'),
            'supplier_order_reference' => fake()->optional()->bothify('SUPREF-####'),
            'status' => 'open',
            'ordered_at' => now()->subDays(7),
            'expected_arrival_date' => now()->addDays(14)->toDateString(),
            'confirmed_arrival_date' => null,
            'ready_date' => null,
            'shipped_date' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
