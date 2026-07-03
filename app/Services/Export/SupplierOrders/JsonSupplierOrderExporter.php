<?php

namespace App\Services\Export\SupplierOrders;

use App\Contracts\Export\SupplierOrderExporterInterface;
use App\Models\SupplierOrder;
use App\Services\Export\SupplierOrders\Concerns\BuildsSupplierOrderExportPayload;

class JsonSupplierOrderExporter implements SupplierOrderExporterInterface
{
    use BuildsSupplierOrderExportPayload;

    public function export(SupplierOrder $order, array $options = []): array
    {
        $this->loadOrderForExport($order);
        $supplierSkus = $this->supplierSkuMap($order);

        $payload = [
            'format_version' => '1',
            'generated_at' => now()->toISOString(),
            'generated_by_user_id' => $options['generated_by_user_id'] ?? null,
            'supplier_order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_date' => $order->order_date?->toDateString(),
                'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
                'currency' => $order->supplier?->default_currency,
            ],
            'company' => [
                'id' => $order->company?->id,
                'name' => $order->company?->name,
                'code' => $order->company?->code,
            ],
            'supplier' => [
                'id' => $order->supplier?->id,
                'name' => $order->supplier?->name,
                'code' => $order->supplier?->code,
                'default_language' => $order->supplier?->default_language,
            ],
            'items' => $order->items
                ->map(fn ($item): array => [
                    'sku' => $item->product?->sku,
                    'manufacturer_sku' => $item->product?->manufacturer_sku,
                    'supplier_sku' => $supplierSkus->get($item->product_id),
                    'product_name' => $item->product?->name,
                    'ordered_quantity' => $item->ordered_quantity,
                    'unit' => $item->product?->unit,
                    'unit_price' => $item->unit_price,
                    'currency' => $item->currency,
                    'notes' => $item->notes,
                ])
                ->values()
                ->all(),
        ];

        $content = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->writeExportFile($order, sprintf('%s.json', $order->order_number), $content, 'application/json');
    }
}
