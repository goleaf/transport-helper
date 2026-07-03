<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportAnalyticsReportRequest;
use App\Services\Supply\Analytics\AnalyticsExportService;
use App\Services\Supply\Analytics\ReportRunService;
use Illuminate\Http\RedirectResponse;

class AnalyticsExportController extends Controller
{
    public function store(ExportAnalyticsReportRequest $request, string $reportType, ReportRunService $runs, AnalyticsExportService $exports): RedirectResponse
    {
        $validated = $request->validated();
        $result = $runs->run($reportType, $validated, $request->user());
        $format = (string) $validated['format'];
        $export = $format === 'json'
            ? $exports->exportJson($reportType, $result['report'], $result['report']['filters'] ?? [], $request->user())
            : $exports->exportCsv($reportType, $result['report'], $result['report']['filters'] ?? [], $request->user());

        return redirect()
            ->route('supply.analytics.report-runs.show', $result['report_run'])
            ->with('status', 'Analytics export created: '.$export['filename']);
    }
}
