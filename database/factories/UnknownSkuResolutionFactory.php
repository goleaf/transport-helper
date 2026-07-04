<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\UnknownSkuResolution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnknownSkuResolution>
 */
class UnknownSkuResolutionFactory extends Factory
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
            'unknown_sku' => 'UNKNOWN-'.$this->faker->unique()->numerify('#####'),
            'source_type' => 'import',
            'source_reference' => null,
            'status' => 'unresolved',
            'metadata_json' => [],
            'created_by_user_id' => User::factory(),
        ];
    }
}
