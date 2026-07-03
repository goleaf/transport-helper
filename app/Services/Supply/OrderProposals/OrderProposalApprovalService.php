<?php

namespace App\Services\Supply\OrderProposals;

use App\Enums\OrderProposalStatus;
use App\Models\OrderProposal;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\OrderProposals\Concerns\FormatsProposalValues;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderProposalApprovalService
{
    use FormatsProposalValues;

    public function __construct(
        private readonly OrderProposalSummaryService $summaryService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function approveProposal(OrderProposal $proposal, User $user): array
    {
        return DB::transaction(function () use ($proposal, $user): array {
            $proposal->refresh();
            $proposal->loadMissing('items');

            if ($this->statusValue($proposal->status) === OrderProposalStatus::ConvertedToSupplierOrder->value) {
                throw ValidationException::withMessages([
                    'proposal' => 'Converted proposals cannot be approved again.',
                ]);
            }

            $summary = $this->summaryService->summarize($proposal);

            if ($summary['unresolved_count'] > 0) {
                throw ValidationException::withMessages([
                    'proposal' => "Cannot approve proposal with {$summary['unresolved_count']} unresolved item(s).",
                ]);
            }

            if ($summary['orderable_count'] === 0) {
                throw ValidationException::withMessages([
                    'proposal' => 'Cannot approve proposal without approved or adjusted positive-quantity lines.',
                ]);
            }

            $oldStatus = $this->statusValue($proposal->status);

            $proposal->forceFill([
                'status' => OrderProposalStatus::Approved,
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
            ])->save();

            $proposal->refresh();
            $summary = $this->summaryService->summarize($proposal);

            $metadata = [
                'proposal_id' => $proposal->id,
                'supplier_id' => $proposal->supplier_id,
                'total_lines' => $summary['total_lines'],
                'orderable_count' => $summary['orderable_count'],
                'total_approved_quantity' => $summary['total_approved_quantity'],
            ];

            $this->auditLogService->logStatusChanged(
                $proposal,
                $oldStatus,
                $this->statusValue($proposal->status),
                $user,
                $metadata,
            );

            $this->auditLogService->logDecision('order_proposal_approved', $proposal, $user, $metadata);

            return [
                'proposal' => $proposal,
                'summary' => $summary,
                'message' => 'Order proposal approved.',
            ];
        });
    }
}
