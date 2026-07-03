<?php

namespace App\Services\Import\Normalizers;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\ImportValueNormalizer;
use App\Services\Import\Normalizers\Concerns\ResolvesImportAliases;

class ProductRuleNormalizer implements ImportNormalizerInterface
{
    use ResolvesImportAliases;

    public function __construct(private ImportValueNormalizer $values) {}

    public function normalize(array $row, array $context = []): array
    {
        $orderEnabled = $this->firstValue($row, ['order_enabled']);

        return [
            'company_id' => $context['company_id'] ?? null,
            'supplier_id' => $context['supplier_id'] ?? null,
            'sku' => $this->values->sku($this->firstValue($row, ['sku', 'product_sku'])),
            'supplier_sku' => $this->values->stringOrNull($this->firstValue($row, ['supplier_sku'])),
            'manufacturer_sku' => $this->values->stringOrNull($this->firstValue($row, ['manufacturer_sku'])),
            'moq' => $this->values->decimalOrNull($this->firstValue($row, ['moq', 'minimum_order_quantity'])),
            'pack_multiple' => $this->values->decimalOrNull($this->firstValue($row, ['pack_multiple', 'package_multiple', 'pack_qty'])),
            'pallet_quantity' => $this->values->decimalOrNull($this->firstValue($row, ['pallet_quantity', 'pallet_qty'])),
            'min_transport_quantity' => $this->values->decimalOrNull($this->firstValue($row, ['min_transport_quantity'])),
            'lead_time_days' => $this->values->integerOrNull($this->firstValue($row, ['lead_time_days'])),
            'safety_days' => $this->values->integerOrNull($this->firstValue($row, ['safety_days'])),
            'safety_rule_type' => $this->values->stringOrNull($this->firstValue($row, ['safety_rule_type'])),
            'transport_rule_type' => $this->values->stringOrNull($this->firstValue($row, ['transport_rule_type'])),
            'order_enabled' => $orderEnabled === null ? true : $this->values->boolean($orderEnabled),
            'source_type' => $context['source_type'] ?? 'csv',
            'source_reference' => $this->values->stringOrNull($this->firstValue($row, ['source_reference'])) ?? ($context['source_reference'] ?? null),
            'import_batch_id' => $context['import_batch_id'] ?? null,
        ];
    }
}
