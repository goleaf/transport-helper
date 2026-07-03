<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApproveIntegrationRequest;
use App\Models\IntegrationConnection;
use App\Services\Supply\Integrations\IntegrationApprovalService;
use App\Services\Supply\Integrations\IntegrationConfigService;
use Illuminate\Http\RedirectResponse;

class IntegrationApprovalController extends Controller
{
    public function submitApproval(ApproveIntegrationRequest $request, IntegrationConnection $connection, IntegrationApprovalService $service): RedirectResponse
    {
        $service->submitForApproval($connection, $request->user(), $request->validated('reason'));

        return back()->with('status', 'Integration submitted for approval.');
    }

    public function approve(ApproveIntegrationRequest $request, IntegrationConnection $connection, IntegrationApprovalService $service): RedirectResponse
    {
        $service->approve($connection, $request->user(), $request->validated('reason'));

        return back()->with('status', 'Integration approved.');
    }

    public function reject(ApproveIntegrationRequest $request, IntegrationConnection $connection, IntegrationApprovalService $service): RedirectResponse
    {
        $service->reject($connection, $request->user(), $request->validated('reason') ?? 'Rejected by user.');

        return back()->with('status', 'Integration rejected.');
    }

    public function revoke(ApproveIntegrationRequest $request, IntegrationConnection $connection, IntegrationApprovalService $service): RedirectResponse
    {
        $service->revoke($connection, $request->user(), $request->validated('reason') ?? 'Revoked by user.');

        return back()->with('status', 'Integration revoked.');
    }

    public function activate(ApproveIntegrationRequest $request, IntegrationConnection $connection, IntegrationApprovalService $service): RedirectResponse
    {
        $service->activate($connection, $request->user(), $request->validated());

        return back()->with('status', 'Integration activated.');
    }

    public function disable(ApproveIntegrationRequest $request, IntegrationConnection $connection, IntegrationConfigService $service): RedirectResponse
    {
        $service->disableConnection($connection, $request->user(), $request->validated('reason') ?? 'Disabled by user.');

        return back()->with('status', 'Integration disabled.');
    }
}
