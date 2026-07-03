<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\DecideProcurementApprovalRequest;
use App\Models\ProcurementApprovalRequest;
use App\Services\Supply\Procurement\ProcurementApprovalWorkflowService;
use Illuminate\Http\RedirectResponse;

class ProcurementApprovalDecisionController extends Controller
{
    public function approve(DecideProcurementApprovalRequest $request, ProcurementApprovalRequest $approvalRequest, ProcurementApprovalWorkflowService $service): RedirectResponse
    {
        $service->approve($approvalRequest, $request->user(), (string) ($request->validated()['note'] ?? 'Approved by manager.'));

        return redirect()->route('supply.procurement.approvals.show', $approvalRequest)->with('status', 'Procurement approval approved.');
    }

    public function reject(DecideProcurementApprovalRequest $request, ProcurementApprovalRequest $approvalRequest, ProcurementApprovalWorkflowService $service): RedirectResponse
    {
        $service->reject($approvalRequest, $request->user(), (string) ($request->validated()['reason'] ?? $request->validated()['note'] ?? 'Rejected by manager.'));

        return redirect()->route('supply.procurement.approvals.show', $approvalRequest)->with('status', 'Procurement approval rejected.');
    }
}
