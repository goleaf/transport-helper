<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesHistory>
 */
class SalesHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'sales_date' => now()->subDays(fake()->numberBetween(1, 90))->toDateString(),
            'quantity' => fake()->numberBetween(1, 25),
            'channel' => fake()->optional()->randomElement(['b2b', 'ecommerce', 'retail']),
            'customer_id' => fake()->optional()->bothify('CUST-####'),
            'is_promotion' => false,
            'is_anomaly' => false,
            'anomaly_reason' => null,
            'source_type' => 'manual',
            'source_reference' => fake()->optional()->uuid(),
            'import_batch_id' => null,
        ];
    }
}
