<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\ProcurementException;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementException>
 */
class ProcurementExceptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'exception_type' => 'budget_overrun',
            'exceptable_type' => OrderProposal::class,
            'exceptable_id' => OrderProposal::factory(),
            'status' => 'pending',
            'reason' => 'Urgent procurement exception request.',
            'requested_by_user_id' => User::factory(),
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'metadata_json' => [],
        ];
    }
}
