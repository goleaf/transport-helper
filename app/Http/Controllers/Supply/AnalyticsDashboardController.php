<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Services\Supply\Analytics\ManagementDashboardAnalyticsService;
use App\Services\Supply\UI\AnalyticsPresentationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ManagementDashboardAnalyticsService $service,
        AnalyticsPresentationService $presentation
    ): View {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->hasPermissionTo('view_analytics'), 403);
        $dashboard = $service->dashboard($request->query(), $request->user());

        return view('supply.analytics.dashboard', [
            'dashboard' => $dashboard,
            'summaryCards' => $presentation->summaryCards($dashboard['summary'] ?? []),
            'reportLinks' => $presentation->reportLinks(),
            'topRisksTable' => $presentation->table($dashboard['top_risks'] ?? []),
        ]);
    }
}
