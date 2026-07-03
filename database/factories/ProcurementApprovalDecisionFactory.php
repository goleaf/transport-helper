<?php

namespace Database\Factories;

use App\Models\ProcurementApprovalDecision;
use App\Models\ProcurementApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementApprovalDecision>
 */
class ProcurementApprovalDecisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'procurement_approval_request_id' => ProcurementApprovalRequest::factory(),
            'decision' => 'approved',
            'decision_by_user_id' => User::factory(),
            'note' => 'Approved for procurement test.',
            'metadata_json' => [],
            'decided_at' => now(),
        ];
    }
}
