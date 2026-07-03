<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ReplenishmentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReplenishmentProfile>
 */
class ReplenishmentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => null,
            'product_id' => null,
            'category' => null,
            'name' => fake()->words(3, true),
            'status' => 'active',
            'priority' => fake()->numberBetween(10, 200),
            'lead_time_days_override' => null,
            'safety_days_override' => null,
            'safety_stock_multiplier' => null,
            'seasonality_enabled' => false,
            'seasonality_mode' => 'none',
            'exclude_promotions' => true,
            'exclude_anomalies' => true,
            'outlier_detection_enabled' => false,
            'outlier_multiplier' => 3.0,
            'reservation_strategy' => 'reserved_not_removed_from_free_stock',
            'pallet_strategy' => 'show_only',
            'transport_strategy' => 'show_only',
            'strategic_minimum_order_enabled' => false,
            'config_json' => null,
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_by_user_id' => User::factory(),
            'updated_by_user_id' => null,
        ];
    }
}
