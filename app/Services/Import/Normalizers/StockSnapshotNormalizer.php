<?php

namespace App\Services\Import\Normalizers;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\ImportValueNormalizer;
use App\Services\Import\Normalizers\Concerns\ResolvesImportAliases;

class StockSnapshotNormalizer implements ImportNormalizerInterface
{
    use ResolvesImportAliases;

    public function __construct(private ImportValueNormalizer $values) {}

    public function normalize(array $row, array $context = []): array
    {
        return [
            'company_id' => $context['company_id'] ?? null,
            'sku' => $this->values->sku($this->firstValue($row, ['sku', 'product_sku', 'product_code', 'item_code'])),
            'snapshot_date' => $this->values->dateOrNull($this->firstValue($row, ['snapshot_date', 'date', 'stock_date'])),
            'free_stock' => $this->values->decimalOrNull($this->firstValue($row, ['free_stock', 'available_stock', 'available_qty', 'free_qty'])),
            'total_stock' => $this->values->decimalOrNull($this->firstValue($row, ['total_stock', 'stock', 'total_qty'])),
            'reserved_quantity' => $this->values->decimalOrNull($this->firstValue($row, ['reserved_quantity', 'reserved_qty', 'reserved'])),
            'damaged_quantity' => $this->values->decimalOrNull($this->firstValue($row, ['damaged_quantity', 'damaged_qty', 'damaged'])),
            'inactive_quantity' => $this->values->decimalOrNull($this->firstValue($row, ['inactive_quantity', 'inactive_qty', 'inactive'])),
            'in_transit_quantity' => $this->values->decimalOrNull($this->firstValue($row, ['in_transit_quantity', 'in_transit_qty', 'transit'])),
            'source_type' => $context['source_type'] ?? 'csv',
            'source_reference' => $this->values->stringOrNull($this->firstValue($row, ['source_reference'])) ?? ($context['source_reference'] ?? null),
            'import_batch_id' => $context['import_batch_id'] ?? null,
        ];
    }
}
