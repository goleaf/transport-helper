<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RequestProcurementApprovalRequest;
use App\Models\ProcurementApprovalRequest;
use App\Services\Supply\Procurement\ProcurementApprovalWorkflowService;
use App\Services\Supply\Procurement\ProcurementComplianceService;
use App\Services\Supply\Procurement\ProcurementSubjectResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProcurementApprovalRequestController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ProcurementApprovalRequest::class);

        return view('supply.procurement.approvals.index', [
            'approvalRequests' => ProcurementApprovalRequest::query()
                ->select(['id', 'company_id', 'approvable_type', 'approvable_id', 'status', 'requested_by_user_id', 'required_role', 'required_permission', 'amount', 'currency', 'reason', 'created_at'])
                ->with(['company:id,name', 'requestedBy:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function show(ProcurementApprovalRequest $approvalRequest): View
    {
        Gate::authorize('view', $approvalRequest);

        $approvalRequest->load(['company:id,name', 'requestedBy:id,name', 'decisions.decisionBy:id,name']);

        return view('supply.procurement.approvals.show', ['approvalRequest' => $approvalRequest]);
    }

    public function store(
        RequestProcurementApprovalRequest $request,
        ProcurementSubjectResolver $resolver,
        ProcurementComplianceService $complianceService,
        ProcurementApprovalWorkflowService $workflowService,
    ): RedirectResponse {
        $validated = $request->validated();
        $subject = $resolver->resolve($validated['approvable_type'], (int) $validated['approvable_id']);
        $compliance = $complianceService->check($subject);
        $result = $workflowService->requestApproval($subject, $compliance['approval_requirements']['requirements'] ?? [], $request->user(), $validated['reason']);

        return redirect()->route('supply.procurement.approvals.show', $result['request'])->with('status', 'Procurement approval requested.');
    }
}
