<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApproveOrderProposalRequest;
use App\Models\OrderProposal;
use App\Services\Supply\OrderProposals\OrderProposalApprovalService;
use Illuminate\Http\RedirectResponse;

class OrderProposalApprovalController extends Controller
{
    public function approve(
        ApproveOrderProposalRequest $request,
        OrderProposal $proposal,
        OrderProposalApprovalService $approvalService,
    ): RedirectResponse {
        $result = $approvalService->approveProposal($proposal, $request->user());

        return redirect()
            ->route('supply.proposals.show', $result['proposal'])
            ->with('status', $result['message']);
    }
}
