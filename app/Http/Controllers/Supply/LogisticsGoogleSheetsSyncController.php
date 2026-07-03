<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\LogisticsRecord;
use App\Services\Supply\LogisticsGoogleSheetsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LogisticsGoogleSheetsSyncController extends Controller
{
    public function store(Request $request, LogisticsGoogleSheetsSyncService $syncService): RedirectResponse
    {
        Gate::authorize('syncGoogleSheets', LogisticsRecord::class);

        $syncService->sync([
            'user_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('supply.logistics.index')
            ->with('status', 'Google Sheets sync completed.');
    }
}
