<?php

namespace App\Services\Import\Normalizers;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\ImportValueNormalizer;
use App\Services\Import\Normalizers\Concerns\ResolvesImportAliases;

class InboundOrderNormalizer implements ImportNormalizerInterface
{
    use ResolvesImportAliases;

    public function __construct(private ImportValueNormalizer $values) {}

    public function normalize(array $row, array $context = []): array
    {
        return [
            'company_id' => $context['company_id'] ?? null,
            'supplier_id' => $context['supplier_id'] ?? null,
            'order_number' => $this->values->stringOrNull($this->firstValue($row, ['order_number', 'po_number', 'purchase_order'])),
            'supplier_order_reference' => $this->values->stringOrNull($this->firstValue($row, ['supplier_order_reference', 'supplier_reference'])),
            'sku' => $this->values->sku($this->firstValue($row, ['sku', 'product_sku'])),
            'ordered_quantity' => $this->values->decimalOrNull($this->firstValue($row, ['ordered_quantity', 'quantity', 'qty'])),
            'confirmed_quantity' => $this->values->decimalOrNull($this->firstValue($row, ['confirmed_quantity'])),
            'expected_arrival_date' => $this->values->dateOrNull($this->firstValue($row, ['expected_arrival_date', 'eta', 'arrival_date'])),
            'confirmed_arrival_date' => $this->values->dateOrNull($this->firstValue($row, ['confirmed_arrival_date'])),
            'ready_date' => $this->values->dateOrNull($this->firstValue($row, ['ready_date'])),
            'shipped_date' => $this->values->dateOrNull($this->firstValue($row, ['shipped_date'])),
            'status' => $this->values->stringOrNull($this->firstValue($row, ['status'])) ?? 'ordered',
            'notes' => $this->values->stringOrNull($this->firstValue($row, ['notes'])),
            'source_type' => $context['source_type'] ?? 'csv',
            'source_reference' => $this->values->stringOrNull($this->firstValue($row, ['source_reference'])) ?? ($context['source_reference'] ?? null),
            'import_batch_id' => $context['import_batch_id'] ?? null,
        ];
    }
}
