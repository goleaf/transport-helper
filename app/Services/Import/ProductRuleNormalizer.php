<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportNormalizerInterface;
use App\Services\Import\Concerns\NormalizesImportValues;

class ProductRuleNormalizer implements ImportNormalizerInterface
{
    use NormalizesImportValues;

    public function normalize(array $row, array $context = []): array
    {
        return [
            'supplier_code' => $this->stringValue($row, 'supplier_code'),
            'sku' => $this->stringValue($row, 'sku'),
            'supplier_sku' => $this->stringValue($row, 'supplier_sku'),
            'moq' => $this->numericValue($row, 'moq'),
            'pack_multiple' => $this->numericValue($row, 'pack_multiple'),
            'pallet_quantity' => $this->numericValue($row, 'pallet_quantity'),
            'min_transport_quantity' => $this->numericValue($row, 'min_transport_quantity'),
            'lead_time_days' => $this->numericValue($row, 'lead_time_days'),
            'safety_days' => $this->numericValue($row, 'safety_days'),
            'safety_rule_type' => $this->stringValue($row, 'safety_rule_type'),
            'transport_rule_type' => $this->stringValue($row, 'transport_rule_type'),
            'order_enabled' => $this->booleanValue($row, 'order_enabled', true),
        ];
    }
}
