<?php

namespace App\Services\Supply\ManufacturerForms;

use App\Models\FormTemplate;
use App\Models\SupplierOrder;
use App\Services\Audit\AuditLogService;

class ManufacturerFormPreviewService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function preview(FormTemplate $template, SupplierOrder $order, array $options = []): array
    {
        $template->refresh();
        $order->loadMissing(['supplier', 'items.product']);
        $mapping = $template->renderer_config_json['manufacturer_mapping']
            ?? $template->mapping_rules_json
            ?? [];

        $header = [
            'order_number' => $order->order_number,
            'order_date' => $order->order_date?->toDateString(),
            'supplier' => $order->supplier?->name,
        ];
        $rows = [];

        foreach ($order->items as $item) {
            $rows[] = [
                'sku' => $item->product?->sku,
                'supplier_sku' => $item->product?->manufacturer_sku,
                'product_name' => $item->product?->name,
                'ordered_quantity' => number_format((float) $item->ordered_quantity, 4, '.', ''),
                'unit' => $item->product?->unit,
            ];
        }

        $warnings = [];

        if (empty($mapping)) {
            $warnings[] = 'mapping_missing';
        }

        $this->auditLogService->write('manufacturer_form_preview_generated', $template, null, null, null, [
            'form_template_id' => $template->id,
            'supplier_order_id' => $order->id,
            'row_count' => count($rows),
            'warnings' => $warnings,
        ], $template->company_id);

        return [
            'template_id' => $template->id,
            'supplier_order_id' => $order->id,
            'mapping' => $mapping,
            'header' => $header,
            'items' => $rows,
            'warnings' => $warnings,
            'missing_mappings' => empty($mapping) ? ['manufacturer_mapping'] : [],
        ];
    }
}
