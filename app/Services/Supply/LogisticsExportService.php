<?php

namespace App\Services\Supply;

use App\Models\ExportFile;
use App\Models\LogisticsRecord;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class LogisticsExportService
{
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
            ->with([
                'supplierOrder:id,order_number',
                'supplier:id,name',
                'carrier:id,name',
            ])
            ->when(isset($filters['company_id']), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('id')
            ->limit((int) ($filters['limit'] ?? 1000))
            ->get();

        $handle = fopen('php://temp', 'w+b');
        fputcsv($handle, [
            'id',
            'supplier_order',
            'supplier',
            'carrier',
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
        ]);

        foreach ($records as $record) {
            fputcsv($handle, [
                $record->id,
                $record->supplierOrder?->order_number,
                $record->supplier?->name,
                $record->carrier?->name,
                $record->order_date?->toDateString(),
                $record->confirmation_date?->toDateString(),
                $record->ready_date?->toDateString(),
                $record->pickup_date?->toDateString(),
                $record->delivery_date?->toDateString(),
                $record->actual_received_date?->toDateString(),
                $record->transport_price,
                $record->currency,
                $record->status?->value,
                $record->external_sheet_reference,
                $record->notes,
            ]);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        $filename = 'logistics-export-'.now()->format('YmdHis').'.csv';
        $path = 'exports/logistics/'.$filename;
        Storage::put($path, $content);

        $export = ExportFile::query()->create([
            'company_id' => $filters['company_id'] ?? $records->first()?->company_id ?? 1,
            'export_type' => 'logistics_csv',
            'related_model_type' => null,
            'related_model_id' => null,
            'filename' => $filename,
            'stored_path' => $path,
            'mime_type' => 'text/csv',
            'status' => 'ready',
            'created_by_user_id' => $user?->id,
        ]);

        return [
            'export' => $export,
            'path' => $path,
            'filename' => $filename,
            'content' => $content,
            'row_count' => $records->count(),
        ];
    }
}
