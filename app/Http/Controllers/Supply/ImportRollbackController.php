<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\Import\ImportBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportRollbackController extends Controller
{
    public function __construct(private ImportBatchService $importBatchService) {}

    public function __invoke(Request $request, ImportBatch $batch): RedirectResponse
    {
        $result = $this->importBatchService->rollback($batch, $request->user());

        return redirect()
            ->route('supply.imports.show', $batch)
            ->with('status', 'Rollback finished: '.$result['rolled_back_count'].' row(s) rolled back, '.$result['skipped_count'].' skipped.');
    }
}
