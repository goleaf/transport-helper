<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\Concerns\NormalizesImportValues;

class ReservationNormalizer implements ImportNormalizerInterface
{
    use NormalizesImportValues;

    public function normalize(array $row, array $context = []): array
    {
        return [
            'sku' => $this->stringValue($row, 'sku'),
            'quantity' => $this->numericValue($row, 'quantity'),
            'project_name' => $this->stringValue($row, 'project_name'),
            'customer_name' => $this->stringValue($row, 'customer_name'),
            'manager_name' => $this->stringValue($row, 'manager_name'),
            'reserved_at' => $this->stringValue($row, 'reserved_at') ?? $this->stringValue($row, 'date'),
            'expected_usage_date' => $this->stringValue($row, 'expected_usage_date'),
            'status' => $this->stringValue($row, 'status', 'active'),
            'source_type' => $this->stringValue($row, 'source_type', 'import'),
            'source_reference' => $this->stringValue($row, 'source_reference'),
        ];
    }
}
