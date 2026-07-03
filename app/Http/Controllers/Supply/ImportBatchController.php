<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreImportBatchRequest;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Services\Import\ImportBatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportBatchController extends Controller
{
    public function __construct(
        private ImportBatchService $importBatchService,
    ) {}

    public function index(): View
    {
        $batches = ImportBatch::query()
            ->select([
                'id',
                'company_id',
                'source_type',
                'source_name',
                'adapter',
                'original_filename',
                'status',
                'total_rows',
                'successful_rows',
                'failed_rows',
                'created_at',
            ])
            ->with(['company:id,name'])
            ->withCount('rows')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.imports.index', [
            'batches' => $batches,
        ]);
    }

    public function create(): View
    {
        $companies = Company::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return view('supply.imports.create', [
            'companies' => $companies,
            'importTypes' => ImportBatchService::IMPORT_TYPES,
        ]);
    }

    public function store(StoreImportBatchRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $path = $request->file('file')->store('imports');
        $batch = $this->importBatchService->import([
            'company_id' => $validated['company_id'],
            'import_type' => $validated['import_type'],
            'adapter' => $validated['adapter'],
            'source_reference' => $validated['source_reference'] ?? null,
            'dry_run' => (bool) ($validated['dry_run'] ?? false),
            'source_path' => Storage::path($path),
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'started_by_user_id' => $request->user()?->getKey(),
        ]);

        return redirect()->route('supply.imports.show', $batch);
    }

    public function show(ImportBatch $batch): View
    {
        $batch->load([
            'company:id,name',
            'rows' => fn ($query) => $query
                ->select([
                    'id',
                    'import_batch_id',
                    'row_number',
                    'status',
                    'error_message',
                    'related_model_type',
                    'related_model_id',
                    'created_at',
                ])
                ->orderBy('row_number'),
        ]);

        return view('supply.imports.show', [
            'batch' => $batch,
        ]);
    }

    public function rollback(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->importBatchService->rollback($batch, $request->user()?->getKey());

        return redirect()->route('supply.imports.show', $batch);
    }
}
