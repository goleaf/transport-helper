<?php

namespace App\Http\Controllers\Supply;

use App\Enums\LogisticsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UpdateLogisticsStatusRequest;
use App\Models\AuditLog;
use App\Models\LogisticsRecord;
use App\Services\Supply\LogisticsRecordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LogisticsController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LogisticsRecord::class);

        $status = $request->string('status')->toString();

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
                'notes',
            ])
            ->with([
                'company:id,name',
                'supplierOrder:id,order_number,status',
                'supplier:id,name',
                'carrier:id,name',
            ])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.logistics.index', [
            'records' => $records,
            'statuses' => LogisticsStatus::cases(),
            'selectedStatus' => $status,
        ]);
    }

    public function show(LogisticsRecord $record): View
    {
        Gate::authorize('view', $record);

        $record->loadMissing([
            'company:id,name',
            'supplierOrder:id,order_number,status,order_date',
            'supplier:id,name',
            'carrier:id,name',
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

    public function updateStatus(
        UpdateLogisticsStatusRequest $request,
        LogisticsRecord $record,
        LogisticsRecordService $recordService,
    ): RedirectResponse {
        Gate::authorize('update', $record);

        $recordService->updateStatus($record, $request->validated('status'), $request->user());

        return redirect()
            ->route('supply.logistics.show', $record)
            ->with('status', 'Logistics status updated.');
    }
}
