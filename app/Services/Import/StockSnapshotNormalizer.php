<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\Concerns\NormalizesImportValues;

class StockSnapshotNormalizer implements ImportNormalizerInterface
{
    use NormalizesImportValues;

    public function normalize(array $row, array $context = []): array
    {
        return [
            'sku' => $this->stringValue($row, 'sku'),
            'snapshot_date' => $this->stringValue($row, 'snapshot_date') ?? $this->stringValue($row, 'date'),
            'free_stock' => $this->numericValue($row, 'free_stock'),
            'total_stock' => $this->numericValue($row, 'total_stock'),
            'reserved_quantity' => $this->numericValue($row, 'reserved_quantity'),
            'damaged_quantity' => $this->numericValue($row, 'damaged_quantity'),
            'inactive_quantity' => $this->numericValue($row, 'inactive_quantity'),
            'in_transit_quantity' => $this->numericValue($row, 'in_transit_quantity'),
            'source_type' => $this->stringValue($row, 'source_type', 'import'),
            'source_reference' => $this->stringValue($row, 'source_reference'),
        ];
    }
}
