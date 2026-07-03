<?php

namespace App\Services\Export\SupplierOrders\Concerns;

use App\Models\SupplierOrder;
use App\Models\SupplierProductRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

trait BuildsSupplierOrderExportPayload
{
    /**
     * @return list<string>
     */
    protected function headers(): array
    {
        return [
            'order_number',
            'order_date',
            'supplier_name',
            'supplier_code',
            'sku',
            'manufacturer_sku',
            'supplier_sku',
            'product_name',
            'ordered_quantity',
            'unit',
            'unit_price',
            'currency',
            'item_notes',
        ];
    }

    protected function loadOrderForExport(SupplierOrder $order): SupplierOrder
    {
        return $order->loadMissing([
            'company:id,name,code',
            'supplier:id,name,code,default_language,default_currency',
            'items.product:id,company_id,sku,manufacturer_sku,name,unit',
        ]);
    }

    /**
     * @return Collection<int, string|null>
     */
    protected function supplierSkuMap(SupplierOrder $order): Collection
    {
        $productIds = $order->items
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return SupplierProductRule::query()
            ->select(['product_id', 'supplier_sku'])
            ->where('supplier_id', $order->supplier_id)
            ->whereIn('product_id', $productIds)
            ->get()
            ->pluck('supplier_sku', 'product_id');
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function itemRows(SupplierOrder $order): array
    {
        $this->loadOrderForExport($order);
        $supplierSkus = $this->supplierSkuMap($order);

        return $order->items
            ->map(fn ($item): array => [
                'order_number' => $order->order_number,
                'order_date' => $order->order_date?->toDateString(),
                'supplier_name' => $order->supplier?->name,
                'supplier_code' => $order->supplier?->code,
                'sku' => $item->product?->sku,
                'manufacturer_sku' => $item->product?->manufacturer_sku,
                'supplier_sku' => $supplierSkus->get($item->product_id),
                'product_name' => $item->product?->name,
                'ordered_quantity' => $item->ordered_quantity,
                'unit' => $item->product?->unit,
                'unit_price' => $item->unit_price,
                'currency' => $item->currency,
                'item_notes' => $item->notes,
            ])
            ->values()
            ->all();
    }

    protected function writeExportFile(SupplierOrder $order, string $filename, string $content, string $mimeType): array
    {
        $orderNumber = trim((string) $order->order_number);
        $directoryOrderNumber = str_replace(['/', '\\'], '-', $orderNumber === '' ? 'supplier-order-'.$order->id : $orderNumber);
        $storedPath = trim(config('supply.exports.supplier_orders_path', 'exports/supplier-orders'), '/')
            .'/'.$directoryOrderNumber.'/'.$filename;

        Storage::put($storedPath, $content);

        return [
            'filename' => $filename,
            'stored_path' => $storedPath,
            'mime_type' => $mimeType,
            'size_bytes' => Storage::size($storedPath),
            'checksum' => hash('sha256', $content),
            'content_preview' => null,
        ];
    }
}
