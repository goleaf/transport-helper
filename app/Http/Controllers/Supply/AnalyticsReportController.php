<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\AnalyticsReportRequest;
use App\Services\Supply\Analytics\ReportRunService;
use App\Services\Supply\UI\AnalyticsPresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnalyticsReportController extends Controller
{
    public function show(
        AnalyticsReportRequest $request,
        string $reportType,
        ReportRunService $runs,
        AnalyticsPresentationService $presentation
    ): View {
        $result = $runs->run($reportType, $request->validated(), $request->user());
        $report = $result['report'];

        return view('supply.analytics.report', [
            'reportType' => $reportType,
            'report' => $report,
            'reportRun' => $result['report_run'],
            'summaryCards' => $presentation->summaryCards($report['summary'] ?? []),
            'reportRowsTable' => $presentation->table($report['rows'] ?? []),
            'definitionRows' => $presentation->definitions($report['definitions'] ?? []),
        ]);
    }

    public function run(AnalyticsReportRequest $request, string $reportType, ReportRunService $runs): RedirectResponse
    {
        $runs->run($reportType, $request->validated(), $request->user());

        return redirect()
            ->route('supply.analytics.reports.show', ['reportType' => $reportType] + $request->validated())
            ->with('status', 'Analytics report run completed.');
    }
}
