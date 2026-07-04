<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MasterDataMergeProposal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterDataMergeProposal>
 */
class MasterDataMergeProposalFactory extends Factory
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
            'merge_type' => 'product',
            'source_model_type' => Product::class,
            'source_model_id' => Product::factory(),
            'target_model_type' => Product::class,
            'target_model_id' => Product::factory(),
            'status' => 'draft',
            'reason' => 'Factory merge proposal.',
            'impact_json' => [],
            'proposed_by_user_id' => User::factory(),
        ];
    }
}
