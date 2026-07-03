<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\ProcurementApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementApprovalRequest>
 */
class ProcurementApprovalRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'approvable_type' => OrderProposal::class,
            'approvable_id' => OrderProposal::factory(),
            'status' => 'pending',
            'requested_by_user_id' => User::factory(),
            'required_role' => null,
            'required_permission' => 'approve_order_proposals',
            'amount' => 2500,
            'currency' => 'EUR',
            'reason' => 'Approval required by procurement rule.',
            'metadata_json' => [],
            'expires_at' => null,
            'resolved_at' => null,
        ];
    }
}
