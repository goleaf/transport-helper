<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\ReportRun;
use App\Services\Supply\UI\AnalyticsPresentationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportRunController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->hasPermissionTo('view_analytics'), 403);

        $runs = ReportRun::query()
            ->select(['id', 'report_type', 'status', 'filters_json', 'warnings_json', 'started_by_user_id', 'started_at', 'finished_at', 'created_at'])
            ->with(['startedBy:id,name'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.analytics.report-runs.index', ['runs' => $runs]);
    }

    public function show(Request $request, ReportRun $run, AnalyticsPresentationService $presentation): View
    {
        abort_unless($request->user()?->hasRole('admin') || $request->user()?->hasPermissionTo('view_analytics'), 403);
        $run->loadMissing(['startedBy:id,name']);

        return view('supply.analytics.report-runs.show', [
            'run' => $run,
            'summaryTable' => $presentation->runSummaryTable($run),
        ]);
    }
}
