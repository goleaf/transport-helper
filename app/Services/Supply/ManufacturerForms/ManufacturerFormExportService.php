<?php

namespace App\Services\Supply\ManufacturerForms;

use App\Exceptions\NotConfiguredYetException;
use App\Models\ExportFile;
use App\Models\FormTemplate;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ManufacturerFormExportService
{
    public function __construct(
        private readonly ManufacturerFormPreviewService $previewService,
        private readonly PortalManualFormInstructionService $portalInstructions,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function export(SupplierOrder $order, FormTemplate $template, array $options, User $user): array
    {
        $format = $options['format'] ?? $template->format_type_value ?? $template->format_type?->value ?? 'portal_manual';

        if ($format === 'portal_manual') {
            $instructions = $this->portalInstructions->instructions($template, $order, $options);
            $this->auditLogService->write('manufacturer_form_exported', $template, $user, null, null, [
                'supplier_order_id' => $order->id,
                'format' => 'portal_manual',
                'file_created' => false,
            ], $order->company_id);

            return [
                'format' => 'portal_manual',
                'instructions' => $instructions,
                'export_file' => null,
            ];
        }

        if ($format === 'excel' && ! class_exists(Spreadsheet::class)) {
            throw NotConfiguredYetException::forAdapter('excel_manufacturer_form_renderer');
        }

        $preview = $this->previewService->preview($template, $order, $options);
        $exportFile = ExportFile::query()->create([
            'company_id' => $order->company_id,
            'export_type' => 'manufacturer_form_'.$format,
            'related_model_type' => $order::class,
            'related_model_id' => $order->id,
            'filename' => 'manufacturer-form-'.$order->order_number.'.json',
            'stored_path' => 'exports/manufacturer-forms/manufacturer-form-'.$order->id.'.json',
            'mime_type' => 'application/json',
            'status' => 'stored',
            'created_by_user_id' => $user->id,
        ]);

        $this->auditLogService->write('manufacturer_form_exported', $template, $user, null, null, [
            'supplier_order_id' => $order->id,
            'format' => $format,
            'export_file_id' => $exportFile->id,
        ], $order->company_id);

        return [
            'format' => $format,
            'preview' => $preview,
            'export_file' => $exportFile,
        ];
    }
}
