<?php

namespace App\Services\Import\Normalizers;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\ImportValueNormalizer;
use App\Services\Import\Normalizers\Concerns\ResolvesImportAliases;

class ReservationNormalizer implements ImportNormalizerInterface
{
    use ResolvesImportAliases;

    public function __construct(private ImportValueNormalizer $values) {}

    public function normalize(array $row, array $context = []): array
    {
        return [
            'company_id' => $context['company_id'] ?? null,
            'sku' => $this->values->sku($this->firstValue($row, ['sku', 'product_sku'])),
            'quantity' => $this->values->decimalOrNull($this->firstValue($row, ['quantity', 'qty', 'reserved_qty'])),
            'project_name' => $this->values->stringOrNull($this->firstValue($row, ['project_name', 'project'])),
            'customer_name' => $this->values->stringOrNull($this->firstValue($row, ['customer_name', 'customer'])),
            'manager_name' => $this->values->stringOrNull($this->firstValue($row, ['manager_name', 'manager'])),
            'reserved_at' => $this->values->dateOrNull($this->firstValue($row, ['reserved_at', 'reservation_date'])),
            'expected_usage_date' => $this->values->dateOrNull($this->firstValue($row, ['expected_usage_date', 'usage_date'])),
            'status' => $this->values->stringOrNull($this->firstValue($row, ['status'])) ?? 'active',
            'source_type' => $context['source_type'] ?? 'csv',
            'source_reference' => $this->values->stringOrNull($this->firstValue($row, ['source_reference'])) ?? ($context['source_reference'] ?? null),
            'import_batch_id' => $context['import_batch_id'] ?? null,
        ];
    }
}
