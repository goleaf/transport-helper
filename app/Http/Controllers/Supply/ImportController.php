<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreImportBatchRequest;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\Supplier;
use App\Services\Import\ImportBatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function __construct(private ImportBatchService $importBatchService) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'import_type', 'company_id']);
        $batches = ImportBatch::query()
            ->select([
                'id',
                'company_id',
                'import_type',
                'source_type',
                'source_name',
                'adapter',
                'original_filename',
                'status',
                'total_rows',
                'successful_rows',
                'failed_rows',
                'started_at',
                'finished_at',
                'created_at',
            ])
            ->with(['company:id,name'])
            ->withCount('rows')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['import_type'] ?? null, fn ($query, string $importType) => $query->where('import_type', $importType))
            ->when($filters['company_id'] ?? null, fn ($query, string $companyId) => $query->where('company_id', $companyId))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.imports.index', [
            'batches' => $batches,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $companies = Company::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
        $suppliers = Supplier::query()
            ->select(['id', 'company_id', 'name'])
            ->orderBy('name')
            ->get();

        return view('supply.imports.create', [
            'companies' => $companies,
            'suppliers' => $suppliers,
            'importTypes' => ImportBatchService::IMPORT_TYPES,
            'adapters' => ['csv', 'manual_json', 'excel', 'google_sheets', 'api', 'email_attachment'],
        ]);
    }

    public function store(StoreImportBatchRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $uploadedFile = $request->file('file');
        $storedPath = $uploadedFile?->store('imports');

        $result = $this->importBatchService->run(
            $validated['import_type'],
            $validated['adapter'],
            [
                'file_path' => $storedPath === false || $storedPath === null ? null : Storage::disk('local')->path($storedPath),
                'delimiter' => $validated['delimiter'] ?? ',',
                'has_header' => (bool) ($validated['has_header'] ?? true),
            ],
            [
                'company_id' => $validated['company_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'dry_run' => (bool) ($validated['dry_run'] ?? false),
                'source_type' => $validated['adapter'],
                'source_name' => $validated['source_reference'] ?? null,
                'original_filename' => $uploadedFile?->getClientOriginalName(),
                'allow_duplicate' => (bool) ($validated['allow_duplicate'] ?? false),
                'allow_negative_stock' => (bool) ($validated['allow_negative_stock'] ?? false),
                'date_format' => $validated['date_format'] ?? null,
            ],
            $request->user(),
        );

        return redirect()
            ->route('supply.imports.show', $result['batch'])
            ->with('status', 'Import finished.');
    }

    public function show(ImportBatch $batch): View
    {
        $batch->load(['company:id,name']);
        $rows = $batch->rows()
            ->select([
                'id',
                'import_batch_id',
                'row_number',
                'raw_json',
                'normalized_json',
                'status',
                'error_message',
                'related_model_type',
                'related_model_id',
                'created_at',
            ])
            ->orderBy('row_number')
            ->paginate(50)
            ->withQueryString();
        $status = $batch->status instanceof \BackedEnum ? $batch->status->value : $batch->status;
        $canRollback = ! in_array($status, ['dry_run', 'rolled_back', 'failed'], true)
            && $batch->rows()
                ->where('status', 'persisted')
                ->exists();

        return view('supply.imports.show', [
            'batch' => $batch,
            'rows' => $rows,
            'canRollback' => $canRollback,
        ]);
    }
}
