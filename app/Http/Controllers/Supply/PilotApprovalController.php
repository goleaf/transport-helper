<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApprovePilotRequest;
use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PilotApprovalController extends Controller
{
    public function approveUat(ApprovePilotRequest $request, PilotSupplier $pilot, PilotApprovalService $service): RedirectResponse
    {
        $service->approveForUat($pilot, $request->user(), (string) $request->validated('note'));

        return back()->with('status', 'Pilot approved for UAT.');
    }

    public function approveLive(ApprovePilotRequest $request, PilotSupplier $pilot, PilotApprovalService $service): RedirectResponse
    {
        $service->approveForLive($pilot, $request->user(), (string) $request->validated('note'));

        return back()->with('status', 'Pilot approved for live. Integrations were not activated automatically.');
    }

    public function block(Request $request, PilotSupplier $pilot, PilotApprovalService $service): RedirectResponse
    {
        Gate::authorize('block', $pilot);

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $service->block($pilot, $request->user(), $validated['note']);

        return back()->with('status', 'Pilot blocked.');
    }
}
