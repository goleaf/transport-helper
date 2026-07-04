<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\CreateMasterDataChangeRequestRequest;
use App\Models\Company;
use App\Models\MasterDataChangeRequest;
use App\Services\Supply\MasterData\MasterDataChangeRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MasterDataChangeRequestController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', MasterDataChangeRequest::class);

        return view('supply.master-data.change-requests.index', [
            'requests' => MasterDataChangeRequest::query()
                ->select(['id', 'company_id', 'request_type', 'status', 'requested_by_user_id', 'reason', 'created_at'])
                ->with(['company:id,name', 'requestedBy:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function store(CreateMasterDataChangeRequestRequest $request, MasterDataChangeRequestService $service): RedirectResponse
    {
        $result = $service->createRequest($request->validated(), $request->user());

        return redirect()->route('supply.master-data.change-requests.show', $result['request'])->with('status', 'Master data change request created.');
    }

    public function show(MasterDataChangeRequest $changeRequest): View
    {
        Gate::authorize('view', $changeRequest);
        $changeRequest->load(['company:id,name', 'requestedBy:id,name', 'approvedBy:id,name', 'rejectedBy:id,name', 'appliedBy:id,name']);

        return view('supply.master-data.change-requests.show', [
            'changeRequest' => $changeRequest,
            'changeRows' => collect($changeRequest->requested_changes_json ?? [])->map(fn (mixed $value, string $key): array => [
                'label' => str_replace('_', ' ', ucfirst($key)),
                'value' => is_scalar($value) ? (string) $value : 'Structured value',
            ])->values()->all(),
        ]);
    }
}
