<?php

namespace Database\Factories;

use App\Models\CalculationScenario;
use App\Models\CalculationScenarioItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalculationScenarioItem>
 */
class CalculationScenarioItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'calculation_scenario_id' => CalculationScenario::factory(),
            'product_id' => Product::factory(),
            'base_order_proposal_item_id' => null,
            'status' => 'simulated',
            'base_raw_need' => null,
            'base_recommended_quantity' => null,
            'simulated_raw_need' => fake()->randomFloat(4, 0, 500),
            'simulated_recommended_quantity' => fake()->randomFloat(4, 0, 500),
            'difference_quantity' => null,
            'trend_used' => null,
            'seasonality_factor' => null,
            'manual_trend_override_id' => null,
            'applied_profile_id' => null,
            'input_json' => [],
            'output_json' => [],
            'explanation_json' => [],
            'warnings_json' => [],
            'requires_human_review' => false,
        ];
    }
}
