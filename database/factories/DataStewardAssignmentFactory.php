<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\DataStewardAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataStewardAssignment>
 */
class DataStewardAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'stewardship_type' => 'category',
            'supplier_id' => null,
            'product_id' => null,
            'category' => 'filters',
            'is_active' => true,
            'notes' => 'Factory steward assignment.',
            'assigned_by_user_id' => User::factory(),
        ];
    }
}
