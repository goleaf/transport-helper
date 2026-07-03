<?php

namespace App\Http\Controllers\Supply;

use App\Enums\LogisticsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UpdateLogisticsRecordRequest;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\LogisticsRecord;
use App\Models\Supplier;
use App\Services\Supply\Logistics\LogisticsRecordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LogisticsController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LogisticsRecord::class);

        $records = LogisticsRecord::query()
            ->select([
                'id',
                'company_id',
                'supplier_order_id',
                'supplier_id',
                'carrier_id',
                'order_date',
                'confirmation_date',
                'ready_date',
                'pickup_date',
                'delivery_date',
                'actual_received_date',
                'transport_price',
                'currency',
                'status',
                'external_sheet_reference',
                'delay_reason',
                'notes',
            ])
            ->with(['supplierOrder:id,order_number,status', 'supplier:id,name', 'carrier:id,name'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('carrier_id'), fn ($query) => $query->where('carrier_id', $request->integer('carrier_id')))
            ->when($request->boolean('delayed_only'), fn ($query) => $query->where('status', LogisticsStatus::Delayed->value))
            ->when($request->boolean('needs_review'), fn ($query) => $query->where('status', LogisticsStatus::NeedsReview->value))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'delayed' => LogisticsRecord::query()->where('status', LogisticsStatus::Delayed->value)->count(),
            'needs_review' => LogisticsRecord::query()->where('status', LogisticsStatus::NeedsReview->value)->count(),
            'expected_soon' => LogisticsRecord::query()->whereDate('delivery_date', '>=', now()->toDateString())->whereDate('delivery_date', '<=', now()->addDays(3)->toDateString())->whereNull('actual_received_date')->count(),
            'ready_for_pickup' => LogisticsRecord::query()->where('status', LogisticsStatus::ReadyForPickup->value)->count(),
            'pickup_scheduled' => LogisticsRecord::query()->where('status', LogisticsStatus::PickupScheduled->value)->count(),
            'in_transit' => LogisticsRecord::query()->where('status', LogisticsStatus::InTransit->value)->count(),
            'completed' => LogisticsRecord::query()->where('status', LogisticsStatus::Completed->value)->count(),
        ];

        return view('supply.logistics.index', [
            'records' => $records,
            'statuses' => LogisticsStatus::cases(),
            'summary' => $summary,
            'suppliers' => Supplier::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'carriers' => Carrier::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'filters' => $request->only(['status', 'supplier_id', 'carrier_id', 'delayed_only', 'needs_review']),
        ]);
    }

    public function show(LogisticsRecord $record): View
    {
        Gate::authorize('view', $record);

        $record->loadMissing([
            'company:id,name',
            'supplierOrder.items.product:id,sku,name',
            'supplier:id,name',
            'carrier:id,name',
            'supplierConfirmation:id,supplier_reference,status',
            'selectedCarrierQuote:id,price,currency,status',
        ]);

        $auditLogs = AuditLog::query()
            ->select(['id', 'event_type', 'auditable_type', 'auditable_id', 'old_values_json', 'new_values_json', 'metadata_json', 'user_id', 'created_at'])
            ->where('auditable_type', LogisticsRecord::class)
            ->where('auditable_id', $record->id)
            ->with('user:id,name')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('supply.logistics.show', [
            'record' => $record,
            'statuses' => LogisticsStatus::cases(),
            'auditLogs' => $auditLogs,
        ]);
    }

    public function edit(LogisticsRecord $record): View
    {
        Gate::authorize('update', $record);

        return view('supply.logistics.edit', [
            'record' => $record->loadMissing(['supplierOrder:id,order_number', 'carrier:id,name']),
            'statuses' => LogisticsStatus::cases(),
            'carriers' => Carrier::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function update(
        UpdateLogisticsRecordRequest $request,
        LogisticsRecord $record,
        LogisticsRecordService $recordService,
    ): RedirectResponse {
        $recordService->manualUpdate($record, $request->validated(), $request->user());

        return redirect()
            ->route('supply.logistics.show', $record)
            ->with('status', 'Logistics record updated.');
    }
}
