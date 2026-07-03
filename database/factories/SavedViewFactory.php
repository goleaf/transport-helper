<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SavedView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedView>
 */
class SavedViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'key' => fake()->unique()->slug(2),
            'route_name' => 'supply.dashboard',
            'model_type' => null,
            'filters_json' => [
                'status' => 'open',
            ],
            'columns_json' => [
                'sku',
                'status',
                'quantity',
            ],
            'sort_json' => [
                'column' => 'created_at',
                'direction' => 'desc',
            ],
            'is_default' => false,
            'is_shared' => false,
            'created_by_user_id' => User::factory(),
        ];
    }
}
