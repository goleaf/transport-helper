<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportLogisticsRequest;
use App\Services\Supply\LogisticsExportService;
use Illuminate\Http\RedirectResponse;

class LogisticsExportController extends Controller
{
    public function store(ExportLogisticsRequest $request, LogisticsExportService $exportService): RedirectResponse
    {
        $result = $exportService->exportCsv($request->validated(), $request->user());

        return redirect()
            ->route('supply.logistics.index')
            ->with('status', sprintf('Logistics export %s created with %s rows.', $result['filename'], $result['row_count']));
    }
}
