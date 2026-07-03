<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\SyncLogisticsGoogleSheetsRequest;
use App\Services\Supply\Logistics\LogisticsGoogleSheetsSyncService;
use Illuminate\Http\RedirectResponse;

class LogisticsGoogleSheetsSyncController extends Controller
{
    public function store(SyncLogisticsGoogleSheetsRequest $request, LogisticsGoogleSheetsSyncService $syncService): RedirectResponse
    {
        $syncService->sync($request->validated('filters') ?? [], $request->user());

        return redirect()
            ->route('supply.logistics.index')
            ->with('status', 'Google Sheets sync completed.');
    }
}
