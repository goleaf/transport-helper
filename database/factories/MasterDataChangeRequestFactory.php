<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MasterDataChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterDataChangeRequest>
 */
class MasterDataChangeRequestFactory extends Factory
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
            'request_type' => 'create_alias',
            'status' => 'draft',
            'requested_by_user_id' => User::factory(),
            'requested_changes_json' => ['alias' => 'Factory alias'],
            'reason' => 'Factory change request.',
        ];
    }
}
