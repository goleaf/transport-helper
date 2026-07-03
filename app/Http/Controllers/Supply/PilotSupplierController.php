<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StorePilotSupplierRequest;
use App\Http\Requests\Supply\UpdatePilotSupplierRequest;
use App\Models\Company;
use App\Models\PilotSupplier;
use App\Models\Supplier;
use App\Services\Supply\Pilot\PilotSupplierService;
use App\Services\Supply\Pilot\PilotUatChecklistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PilotSupplierController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', PilotSupplier::class);

        return view('supply.pilots.index', [
            'pilots' => PilotSupplier::query()
                ->select(['id', 'company_id', 'supplier_id', 'name', 'status', 'readiness_result_json', 'dry_run_result_json', 'approved_by_user_id', 'approved_at', 'created_at'])
                ->with(['company:id,name', 'supplier:id,name', 'approvedBy:id,name'])
                ->withCount(['files', 'runs'])
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', PilotSupplier::class);

        return view('supply.pilots.create', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name'])->orderBy('name')->get(),
            'pilot' => null,
        ]);
    }

    public function store(StorePilotSupplierRequest $request, PilotSupplierService $service): RedirectResponse
    {
        $result = $service->create($request->validated(), $request->user());

        return redirect()
            ->route('supply.pilots.show', $result['pilot'])
            ->with('status', 'Pilot supplier created.');
    }

    public function show(PilotSupplier $pilot, PilotUatChecklistService $uatChecklistService): View
    {
        Gate::authorize('view', $pilot);

        $pilot->load([
            'company:id,name',
            'supplier:id,name,company_id',
            'supplier.contacts:id,supplier_id,name,email,receives_orders,is_active',
            'files:id,pilot_supplier_id,file_type,original_filename,stored_path,checksum,uploaded_by_user_id,created_at',
            'files.uploadedBy:id,name',
            'runs:id,pilot_supplier_id,run_type,status,started_by_user_id,started_at,finished_at',
            'runs.startedBy:id,name',
            'createdBy:id,name',
            'approvedBy:id,name',
        ]);

        return view('supply.pilots.show', [
            'pilot' => $pilot,
            'checklist' => $uatChecklistService->getChecklist($pilot),
            'uatEvaluation' => $uatChecklistService->evaluate($pilot),
            'mappingTexts' => $this->mappingTexts($pilot),
        ]);
    }

    public function edit(PilotSupplier $pilot): View
    {
        Gate::authorize('update', $pilot);

        return view('supply.pilots.edit', [
            'pilot' => $pilot,
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name'])->orderBy('name')->get(),
            'dataSourcesText' => $this->prettyJson($pilot->data_sources_json ?? []),
        ]);
    }

    public function update(UpdatePilotSupplierRequest $request, PilotSupplier $pilot, PilotSupplierService $service): RedirectResponse
    {
        $service->update($pilot, $request->validated(), $request->user());

        return redirect()
            ->route('supply.pilots.show', $pilot)
            ->with('status', 'Pilot supplier updated.');
    }

    /**
     * @return array<string, string>
     */
    private function mappingTexts(PilotSupplier $pilot): array
    {
        return [
            'import' => $this->prettyJson([
                'file_id' => null,
                'adapter' => 'csv',
                'delimiter' => ',',
                'has_header' => true,
                'columns' => [
                    'sku' => 'SKU',
                    'sales_date' => 'Date',
                    'quantity' => 'Qty',
                ],
            ]),
            'manufacturer_form' => $this->prettyJson($pilot->manufacturer_form_mapping_json ?: [
                'header' => ['order_number' => 'B2'],
                'items' => [
                    'start_row' => 10,
                    'columns' => [
                        'sku' => 'A',
                        'ordered_quantity' => 'D',
                    ],
                ],
            ]),
            'email' => $this->prettyJson($pilot->email_sample_mapping_json['supplier_confirmation'] ?? [
                'order_number' => 'subject',
                'ready_date' => 'body_text',
            ]),
            'carrier' => $this->prettyJson($pilot->carrier_mapping_json ?: [
                'carrier_name' => 'from_email',
                'price' => 'body_text',
                'delivery_date' => 'body_text',
            ]),
            'logistics' => $this->prettyJson($pilot->logistics_mapping_json ?: [
                'delivery_date' => 'Delivery Date',
                'carrier' => 'Carrier',
                'status' => 'Status',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function prettyJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
