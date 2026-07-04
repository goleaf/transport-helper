<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\DecideMasterDataChangeRequestRequest;
use App\Models\MasterDataChangeRequest;
use App\Services\Supply\MasterData\MasterDataChangeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MasterDataChangeDecisionController extends Controller
{
    public function approve(DecideMasterDataChangeRequestRequest $request, MasterDataChangeRequest $changeRequest, MasterDataChangeRequestService $service): RedirectResponse
    {
        $service->approve($changeRequest, $request->user(), (string) ($request->validated()['note'] ?? 'Approved.'));

        return redirect()->route('supply.master-data.change-requests.show', $changeRequest)->with('status', 'Change request approved.');
    }

    public function reject(DecideMasterDataChangeRequestRequest $request, MasterDataChangeRequest $changeRequest, MasterDataChangeRequestService $service): RedirectResponse
    {
        $service->reject($changeRequest, $request->user(), (string) ($request->validated()['reason'] ?? 'Rejected.'));

        return redirect()->route('supply.master-data.change-requests.show', $changeRequest)->with('status', 'Change request rejected.');
    }

    public function apply(MasterDataChangeRequest $changeRequest, MasterDataChangeRequestService $service): RedirectResponse
    {
        Gate::authorize('apply', $changeRequest);
        $service->apply($changeRequest, request()->user());

        return redirect()->route('supply.master-data.change-requests.show', $changeRequest)->with('status', 'Approved change request applied.');
    }
}
