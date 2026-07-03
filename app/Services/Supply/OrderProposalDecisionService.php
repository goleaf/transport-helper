<?php

namespace App\Services\Supply;

use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\User;
use App\Services\Supply\OrderProposals\OrderProposalApprovalService;
use App\Services\Supply\OrderProposals\OrderProposalDecisionService as StageOrderProposalDecisionService;

class OrderProposalDecisionService
{
    public function __construct(
        private readonly StageOrderProposalDecisionService $decisionService,
        private readonly OrderProposalApprovalService $approvalService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function approveItem(OrderProposalItem $item, User $user, array $options = []): OrderProposalItem
    {
        return $this->decisionService->approveItem($item, $user, $options)['item'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function adjustItem(OrderProposalItem $item, User $user, array $data): OrderProposalItem
    {
        return $this->decisionService->adjustItem($item, $data, $user)['item'];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function rejectItem(OrderProposalItem $item, User $user, array $data = []): OrderProposalItem
    {
        return $this->decisionService->rejectItem($item, $data, $user)['item'];
    }

    public function approveProposal(OrderProposal $proposal, User $user): OrderProposal
    {
        return $this->approvalService->approveProposal($proposal, $user)['proposal'];
    }

    public function hasUnresolvedItems(OrderProposal $proposal): bool
    {
        return $this->decisionService->hasUnresolvedItems($proposal);
    }
}
