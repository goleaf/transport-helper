<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierAlias;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierAlias>
 */
class SupplierAliasFactory extends Factory
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
            'supplier_id' => Supplier::factory(),
            'alias' => 'Supplier Alias '.$this->faker->unique()->numberBetween(1000, 9999),
            'alias_type' => 'name_alias',
            'source_type' => 'manual',
            'source_reference' => null,
            'status' => 'active',
            'confidence' => 1.0,
            'reason' => 'Factory alias.',
            'approved_by_user_id' => User::factory(),
            'approved_at' => now(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
