<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportPilotReportRequest;
use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotReportService;
use Illuminate\Http\RedirectResponse;

class PilotReportController extends Controller
{
    public function export(ExportPilotReportRequest $request, PilotSupplier $pilot, PilotReportService $service): RedirectResponse
    {
        $validated = $request->validated();
        $result = $validated['format'] === 'json'
            ? $service->exportReportJson($pilot, $validated['report_type'], $request->user())
            : $service->exportReportCsv($pilot, $validated['report_type'], $request->user());

        return redirect()
            ->route('supply.pilots.show', $pilot)
            ->with('status', 'Pilot report exported: '.$result['export_file']->filename);
    }
}
