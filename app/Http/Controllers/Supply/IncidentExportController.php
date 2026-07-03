<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportIncidentReportRequest;
use App\Services\Supply\Incidents\IncidentExportService;
use Illuminate\Http\RedirectResponse;

class IncidentExportController extends Controller
{
    public function store(ExportIncidentReportRequest $request, IncidentExportService $service): RedirectResponse
    {
        $validated = $request->validated();
        $format = $validated['format'];
        $filters = $validated['filters'] ?? [];
        $result = $format === 'json'
            ? $service->exportJson($filters, $request->user())
            : $service->exportCsv($filters, $request->user());

        return redirect()
            ->route('supply.exports.download', $result['export_file'])
            ->with('status', 'Incident report exported.');
    }
}
