<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExecuteMergeProposalRequest;
use App\Models\MasterDataMergeProposal;
use App\Services\Supply\MasterData\MasterDataMergeExecutionService;
use Illuminate\Http\RedirectResponse;

class MasterDataMergeExecutionController extends Controller
{
    public function store(ExecuteMergeProposalRequest $request, MasterDataMergeProposal $proposal, MasterDataMergeExecutionService $service): RedirectResponse
    {
        $service->execute($proposal, $request->user(), $request->validated());

        return redirect()->route('supply.master-data.merge-proposals.show', $proposal)->with('status', 'Approved merge executed safely. Source record was not hard-deleted.');
    }
}
