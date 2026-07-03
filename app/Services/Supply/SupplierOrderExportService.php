<?php

namespace App\Services\Supply;

use App\Contracts\Supply\SupplierOrderExporterInterface;
use App\Exceptions\NotConfiguredYetException;
use App\Models\AuditLog;
use App\Models\ExportFile;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class SupplierOrderExportService implements SupplierOrderExporterInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function export(SupplierOrder $order, User $user, array $options = []): ExportFile
    {
        $format = $this->normalizeFormat((string) ($options['format'] ?? 'csv'));
        $order->loadMissing([
            'company:id,name',
            'supplier:id,name',
            'items.product:id,sku,name',
        ]);

        $content = match ($format) {
            'csv' => $this->toCsv($order),
            'json' => $this->toJson($order),
            'excel_csv' => "\xEF\xBB\xBF".$this->toCsv($order),
            'pdf' => throw new NotConfiguredYetException('Supplier order PDF export is not configured yet.'),
            'supplier_custom_template' => throw new NotConfiguredYetException('Supplier custom template export is not configured yet.'),
            default => throw new NotConfiguredYetException("Supplier order export format [{$format}] is not configured yet."),
        };

        $extension = $format === 'json' ? 'json' : 'csv';
        $filename = sprintf('supplier-order-%s.%s', $order->order_number, $extension);
        $storedPath = sprintf(
            'exports/supplier-orders/%s/%s-%s.%s',
            $order->id,
            now()->format('YmdHis'),
            $format,
            $extension,
        );

        Storage::put($storedPath, $content);

        $exportFile = ExportFile::query()->create([
            'company_id' => $order->company_id,
            'export_type' => 'supplier_order_'.$format,
            'related_model_type' => $order::class,
            'related_model_id' => $order->id,
            'filename' => $filename,
            'stored_path' => $storedPath,
            'mime_type' => $format === 'json' ? 'application/json' : 'text/csv',
            'status' => 'ready',
            'created_by_user_id' => $user->id,
        ]);

        AuditLog::query()->create([
            'company_id' => $order->company_id,
            'user_id' => $user->id,
            'event_type' => 'supplier_order.exported',
            'auditable_type' => $order::class,
            'auditable_id' => $order->id,
            'old_values_json' => [],
            'new_values_json' => [
                'export_file_id' => $exportFile->id,
                'format' => $format,
                'filename' => $filename,
            ],
            'metadata_json' => [
                'checksum' => hash('sha256', $content),
            ],
            'created_at' => now(),
        ]);

        return $exportFile;
    }

    private function normalizeFormat(string $format): string
    {
        return match ($format) {
            'excel-compatible-csv', 'excel_compatible_csv', 'excel_csv' => 'excel_csv',
            'supplier-custom-template', 'supplier_custom_template' => 'supplier_custom_template',
            default => $format,
        };
    }

    private function toCsv(SupplierOrder $order): string
    {
        $handle = fopen('php://temp', 'w+b');

        fputcsv($handle, [
            'order_number',
            'supplier',
            'sku',
            'product_name',
            'ordered_quantity',
            'unit_price',
            'currency',
        ]);

        foreach ($order->items as $item) {
            fputcsv($handle, [
                $order->order_number,
                $order->supplier?->name,
                $item->product?->sku,
                $item->product?->name,
                $item->ordered_quantity,
                $item->unit_price,
                $item->currency,
            ]);
        }

        rewind($handle);

        $content = stream_get_contents($handle);

        fclose($handle);

        return (string) $content;
    }

    private function toJson(SupplierOrder $order): string
    {
        $payload = [
            'order_number' => $order->order_number,
            'supplier' => [
                'id' => $order->supplier?->id,
                'name' => $order->supplier?->name,
            ],
            'items' => $order->items->map(fn ($item): array => [
                'sku' => $item->product?->sku,
                'product_name' => $item->product?->name,
                'ordered_quantity' => (float) $item->ordered_quantity,
                'unit_price' => $item->unit_price === null ? null : (float) $item->unit_price,
                'currency' => $item->currency,
            ])->values()->all(),
        ];

        return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
