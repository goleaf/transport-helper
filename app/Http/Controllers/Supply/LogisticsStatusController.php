<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UpdateLogisticsStatusRequest;
use App\Models\LogisticsRecord;
use App\Services\Supply\Logistics\LogisticsRecordService;
use Illuminate\Http\RedirectResponse;

class LogisticsStatusController extends Controller
{
    public function store(
        UpdateLogisticsStatusRequest $request,
        LogisticsRecord $record,
        LogisticsRecordService $recordService,
    ): RedirectResponse {
        $recordService->updateStatus(
            $record,
            $request->validated('status'),
            $request->validated('reason'),
            $request->user(),
        );

        return redirect()
            ->route('supply.logistics.show', $record)
            ->with('status', 'Logistics status updated.');
    }
}
