<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\Concerns\NormalizesImportValues;

class SalesHistoryNormalizer implements ImportNormalizerInterface
{
    use NormalizesImportValues;

    public function normalize(array $row, array $context = []): array
    {
        return [
            'sku' => $this->stringValue($row, 'sku'),
            'sales_date' => $this->stringValue($row, 'sales_date') ?? $this->stringValue($row, 'date'),
            'quantity' => $this->numericValue($row, 'quantity'),
            'channel' => $this->stringValue($row, 'channel'),
            'customer_id' => $this->stringValue($row, 'customer_id'),
            'is_promotion' => $this->booleanValue($row, 'is_promotion'),
            'is_anomaly' => $this->booleanValue($row, 'is_anomaly'),
            'anomaly_reason' => $this->stringValue($row, 'anomaly_reason'),
            'source_type' => $this->stringValue($row, 'source_type', 'import'),
            'source_reference' => $this->stringValue($row, 'source_reference'),
        ];
    }
}
