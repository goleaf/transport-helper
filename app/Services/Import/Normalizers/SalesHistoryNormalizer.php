<?php

namespace App\Services\Import\Normalizers;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\ImportValueNormalizer;
use App\Services\Import\Normalizers\Concerns\ResolvesImportAliases;

class SalesHistoryNormalizer implements ImportNormalizerInterface
{
    use ResolvesImportAliases;

    public function __construct(private ImportValueNormalizer $values) {}

    public function normalize(array $row, array $context = []): array
    {
        return [
            'company_id' => $context['company_id'] ?? null,
            'sku' => $this->values->sku($this->firstValue($row, ['sku', 'product_sku', 'product_code', 'item_code'])),
            'sales_date' => $this->values->dateOrNull($this->firstValue($row, ['sales_date', 'date', 'sale_date', 'document_date'])),
            'quantity' => $this->values->decimalOrNull($this->firstValue($row, ['quantity', 'qty', 'sold_qty', 'sales_qty'])),
            'channel' => $this->values->stringOrNull($this->firstValue($row, ['channel', 'sales_channel'])),
            'customer_id' => $this->values->stringOrNull($this->firstValue($row, ['customer_id', 'customer'])),
            'is_promotion' => $this->values->boolean($this->firstValue($row, ['is_promotion', 'promotion', 'promo'])),
            'is_anomaly' => $this->values->boolean($this->firstValue($row, ['is_anomaly', 'anomaly'])),
            'anomaly_reason' => $this->values->stringOrNull($this->firstValue($row, ['anomaly_reason'])),
            'source_type' => $context['source_type'] ?? 'csv',
            'source_reference' => $this->values->stringOrNull($this->firstValue($row, ['source_reference'])) ?? ($context['source_reference'] ?? null),
            'import_batch_id' => $context['import_batch_id'] ?? null,
        ];
    }
}
