<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\AdjustOrderProposalItemRequest;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Services\Supply\OrderProposalDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderProposalItemDecisionController extends Controller
{
    public function approve(
        Request $request,
        OrderProposal $proposal,
        OrderProposalItem $item,
        OrderProposalDecisionService $decisionService,
    ): RedirectResponse {
        $this->ensureItemBelongsToProposal($proposal, $item);

        Gate::authorize('approve', $item);

        $decisionService->approveItem($item, $request->user());

        return redirect()
            ->route('supply.proposals.items.show', [$proposal, $item])
            ->with('status', 'Proposal item approved.');
    }

    public function adjust(
        AdjustOrderProposalItemRequest $request,
        OrderProposal $proposal,
        OrderProposalItem $item,
        OrderProposalDecisionService $decisionService,
    ): RedirectResponse {
        $this->ensureItemBelongsToProposal($proposal, $item);

        $decisionService->adjustItem($item, $request->user(), $request->validated());

        return redirect()
            ->route('supply.proposals.items.show', [$proposal, $item])
            ->with('status', 'Proposal item adjusted.');
    }

    public function reject(
        Request $request,
        OrderProposal $proposal,
        OrderProposalItem $item,
        OrderProposalDecisionService $decisionService,
    ): RedirectResponse {
        $this->ensureItemBelongsToProposal($proposal, $item);

        Gate::authorize('reject', $item);

        $decisionService->rejectItem($item, $request->user());

        return redirect()
            ->route('supply.proposals.items.show', [$proposal, $item])
            ->with('status', 'Proposal item rejected.');
    }

    private function ensureItemBelongsToProposal(OrderProposal $proposal, OrderProposalItem $item): void
    {
        abort_unless($item->order_proposal_id === $proposal->id, 404);
    }
}
