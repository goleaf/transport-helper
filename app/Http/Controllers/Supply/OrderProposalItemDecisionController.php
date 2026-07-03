<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\AdjustOrderProposalItemRequest;
use App\Http\Requests\Supply\ApproveOrderProposalItemRequest;
use App\Http\Requests\Supply\RejectOrderProposalItemRequest;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Services\Supply\OrderProposals\OrderProposalDecisionService;
use Illuminate\Http\RedirectResponse;

class OrderProposalItemDecisionController extends Controller
{
    public function approve(
        ApproveOrderProposalItemRequest $request,
        OrderProposal $proposal,
        OrderProposalItem $item,
        OrderProposalDecisionService $decisionService,
    ): RedirectResponse {
        $this->ensureItemBelongsToProposal($proposal, $item);

        $result = $decisionService->approveItem($item, $request->user(), $request->validated());

        return redirect()
            ->route('supply.proposals.items.show', [$proposal, $result['item']])
            ->with('status', $result['message']);
    }

    public function adjust(
        AdjustOrderProposalItemRequest $request,
        OrderProposal $proposal,
        OrderProposalItem $item,
        OrderProposalDecisionService $decisionService,
    ): RedirectResponse {
        $this->ensureItemBelongsToProposal($proposal, $item);

        $result = $decisionService->adjustItem($item, $request->validated(), $request->user());

        return redirect()
            ->route('supply.proposals.items.show', [$proposal, $result['item']])
            ->with('status', $result['message']);
    }

    public function reject(
        RejectOrderProposalItemRequest $request,
        OrderProposal $proposal,
        OrderProposalItem $item,
        OrderProposalDecisionService $decisionService,
    ): RedirectResponse {
        $this->ensureItemBelongsToProposal($proposal, $item);

        $result = $decisionService->rejectItem($item, $request->validated(), $request->user());

        return redirect()
            ->route('supply.proposals.items.show', [$proposal, $result['item']])
            ->with('status', $result['message']);
    }

    private function ensureItemBelongsToProposal(OrderProposal $proposal, OrderProposalItem $item): void
    {
        abort_unless($item->order_proposal_id === $proposal->id, 404);
    }
}
