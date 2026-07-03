<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\Concerns\NormalizesImportValues;

class InboundOrderNormalizer implements ImportNormalizerInterface
{
    use NormalizesImportValues;

    public function normalize(array $row, array $context = []): array
    {
        return [
            'supplier_code' => $this->stringValue($row, 'supplier_code'),
            'sku' => $this->stringValue($row, 'sku'),
            'order_number' => $this->stringValue($row, 'order_number'),
            'supplier_order_reference' => $this->stringValue($row, 'supplier_order_reference'),
            'ordered_quantity' => $this->numericValue($row, 'ordered_quantity'),
            'confirmed_quantity' => $this->numericValue($row, 'confirmed_quantity'),
            'expected_arrival_date' => $this->stringValue($row, 'expected_arrival_date'),
            'confirmed_arrival_date' => $this->stringValue($row, 'confirmed_arrival_date'),
            'status' => $this->stringValue($row, 'status', 'open'),
        ];
    }
}
