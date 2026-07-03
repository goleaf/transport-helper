<?php

namespace App\Services\Supply\Logistics;

use App\Enums\ExportFileStatus;
use App\Models\Company;
use App\Models\ExportFile;
use App\Models\LogisticsRecord;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LogisticsExportService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportCsv(array $filters = [], ?User $user = null): array
    {
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
            ->with(['supplier:id,name', 'supplierOrder:id,order_number', 'carrier:id,name'])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['supplier_id']), fn ($query) => $query->where('supplier_id', $filters['supplier_id']))
            ->when(isset($filters['carrier_id']), fn ($query) => $query->where('carrier_id', $filters['carrier_id']))
            ->when(isset($filters['date_from']), fn ($query) => $query->whereDate('delivery_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('delivery_date', '<=', $filters['date_to']))
            ->when((bool) ($filters['delayed_only'] ?? false), fn ($query) => $query->where('status', 'delayed'))
            ->latest('id')
            ->limit(5000)
            ->get();

        $handle = fopen('php://temp', 'w+b');
        fputcsv($handle, [
            'logistics_record_id',
            'supplier',
            'supplier_order_number',
            'order_date',
            'confirmation_date',
            'ready_date',
            'pickup_date',
            'delivery_date',
            'actual_received_date',
            'carrier',
            'transport_price',
            'currency',
            'status',
            'external_sheet_reference',
            'notes',
        ]);

        foreach ($records as $record) {
            fputcsv($handle, [
                $record->id,
                $record->supplier?->name,
                $record->supplierOrder?->order_number,
                $record->order_date?->toDateString(),
                $record->confirmation_date?->toDateString(),
                $record->ready_date?->toDateString(),
                $record->pickup_date?->toDateString(),
                $record->delivery_date?->toDateString(),
                $record->actual_received_date?->toDateString(),
                $record->carrier?->name,
                $record->transport_price,
                $record->currency,
                $record->status?->value ?? $record->status,
                $record->external_sheet_reference,
                $record->notes,
            ]);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        $filename = 'logistics-export-'.now()->format('YmdHis').'.csv';
        $path = 'exports/logistics/'.$filename;
        Storage::disk('local')->put($path, $content);
        $companyId = $this->companyId($records->first(), $filters);

        $export = ExportFile::query()->create([
            'company_id' => $companyId,
            'export_type' => 'logistics_csv',
            'filename' => $filename,
            'stored_path' => $path,
            'mime_type' => 'text/csv',
            'status' => ExportFileStatus::Stored->value,
            'created_by_user_id' => $user?->id,
        ]);

        $this->auditLogService->logExport($export, 'logistics_exported', $user, [
            'row_count' => $records->count(),
            'filters' => $filters,
        ]);

        return [
            'export' => $export,
            'filename' => $filename,
            'path' => $path,
            'content' => $content,
            'row_count' => $records->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function companyId(?LogisticsRecord $record, array $filters): int
    {
        $companyId = $filters['company_id'] ?? $record?->company_id ?? Company::query()->orderBy('id')->value('id');

        if (! is_numeric($companyId)) {
            throw ValidationException::withMessages(['company_id' => 'A company is required before exporting logistics.']);
        }

        return (int) $companyId;
    }
}
