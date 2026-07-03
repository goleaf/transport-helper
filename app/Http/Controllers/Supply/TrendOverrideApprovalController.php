<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApproveTrendOverrideRequest;
use App\Models\TrendOverride;
use App\Services\Supply\Forecasting\TrendOverrideService;
use Illuminate\Http\RedirectResponse;

class TrendOverrideApprovalController extends Controller
{
    public function approve(ApproveTrendOverrideRequest $request, TrendOverride $override, TrendOverrideService $service): RedirectResponse
    {
        $service->approve($override, $request->user(), (string) $request->validated('note'));

        return redirect()->route('supply.forecasting.overrides.show', $override)->with('status', 'Trend override approved.');
    }

    public function reject(ApproveTrendOverrideRequest $request, TrendOverride $override, TrendOverrideService $service): RedirectResponse
    {
        $service->reject($override, $request->user(), (string) $request->validated('reason'));

        return redirect()->route('supply.forecasting.overrides.show', $override)->with('status', 'Trend override rejected.');
    }

    public function revoke(ApproveTrendOverrideRequest $request, TrendOverride $override, TrendOverrideService $service): RedirectResponse
    {
        $service->revoke($override, $request->user(), (string) $request->validated('reason'));

        return redirect()->route('supply.forecasting.overrides.show', $override)->with('status', 'Trend override revoked.');
    }
}
