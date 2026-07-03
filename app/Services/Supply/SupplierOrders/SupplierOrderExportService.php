<?php

namespace App\Services\Supply\SupplierOrders;

use App\Contracts\Export\SupplierOrderExporterInterface;
use App\Enums\ExportFileStatus;
use App\Exceptions\NotConfiguredYetException;
use App\Models\ExportFile;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Export\SupplierOrders\CsvSupplierOrderExporter;
use App\Services\Export\SupplierOrders\ExcelCsvSupplierOrderExporter;
use App\Services\Export\SupplierOrders\JsonSupplierOrderExporter;
use App\Services\Export\SupplierOrders\PdfSupplierOrderExporterPlaceholder;
use App\Services\Export\SupplierOrders\SupplierCustomTemplateExporterPlaceholder;
use Illuminate\Validation\ValidationException;

class SupplierOrderExportService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CsvSupplierOrderExporter $csvExporter,
        private readonly JsonSupplierOrderExporter $jsonExporter,
        private readonly ExcelCsvSupplierOrderExporter $excelCsvExporter,
        private readonly PdfSupplierOrderExporterPlaceholder $pdfExporter,
        private readonly SupplierCustomTemplateExporterPlaceholder $customTemplateExporter,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function export(SupplierOrder $order, string $format, array $options = [], ?User $user = null): array
    {
        $format = $this->normalizeFormat($format);

        if (! $order->items()->exists()) {
            throw ValidationException::withMessages([
                'supplier_order' => 'Supplier order export requires at least one item.',
            ]);
        }

        $exportInfo = $this->resolveExporter($format)->export($order, $options + [
            'generated_by_user_id' => $user?->id,
        ]);

        $exportFile = ExportFile::query()->create([
            'company_id' => $order->company_id,
            'export_type' => 'supplier_order_'.$format,
            'related_model_type' => $order::class,
            'related_model_id' => $order->id,
            'filename' => $exportInfo['filename'],
            'stored_path' => $exportInfo['stored_path'],
            'mime_type' => $exportInfo['mime_type'],
            'status' => ExportFileStatus::Stored->value,
            'created_by_user_id' => $user?->id,
        ]);

        $metadata = [
            'supplier_order_id' => $order->id,
            'order_number' => $order->order_number,
            'format' => $format,
            'export_file_id' => $exportFile->id,
            'filename' => $exportFile->filename,
            'stored_path' => $exportFile->stored_path,
            'items_count' => $order->items()->count(),
            'checksum' => $exportInfo['checksum'] ?? null,
            'size_bytes' => $exportInfo['size_bytes'] ?? null,
        ];

        $this->auditLogService->logDecision('supplier_order_exported', $order, $user, $metadata);
        $this->auditLogService->logExport($exportFile, 'export_created', $user, $metadata);

        return [
            'export_file' => $exportFile,
            'path' => $exportFile->stored_path,
            'filename' => $exportFile->filename,
            'format' => $format,
        ];
    }

    protected function resolveExporter(string $format): SupplierOrderExporterInterface
    {
        return match ($format) {
            'csv' => $this->csvExporter,
            'json' => $this->jsonExporter,
            'excel_csv' => $this->excelCsvExporter,
            'pdf' => $this->pdfExporter,
            'supplier_custom_template' => $this->customTemplateExporter,
            default => throw NotConfiguredYetException::forAdapter('supplier_order_'.$format.'_export'),
        };
    }

    protected function normalizeFormat(string $format): string
    {
        return match ($format) {
            'excel-compatible-csv', 'excel_compatible_csv', 'excel_csv' => 'excel_csv',
            'supplier-custom-template', 'supplier_custom_template' => 'supplier_custom_template',
            default => $format,
        };
    }
}
