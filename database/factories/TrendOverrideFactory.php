<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TrendOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrendOverride>
 */
class TrendOverrideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'supplier_id' => null,
            'product_id' => null,
            'category' => null,
            'trend_value' => fake()->randomFloat(6, 0.1, 3),
            'date_from' => now()->subDays(10)->toDateString(),
            'date_to' => now()->addDays(20)->toDateString(),
            'status' => 'draft',
            'reason' => fake()->sentence(),
            'approval_note' => null,
            'rejection_reason' => null,
            'created_by_user_id' => User::factory(),
            'approved_by_user_id' => null,
            'approved_at' => null,
            'revoked_by_user_id' => null,
            'revoked_at' => null,
        ];
    }
}
