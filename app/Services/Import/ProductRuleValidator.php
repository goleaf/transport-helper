<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Concerns\ValidatesImportValues;

class ProductRuleValidator implements ImportValidatorInterface
{
    use ValidatesImportValues;

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validationErrors($row, [
            'supplier_code' => ['required', 'string'],
            'sku' => ['required', 'string'],
            'supplier_sku' => ['nullable', 'string'],
            'moq' => ['nullable', 'numeric', 'min:0'],
            'pack_multiple' => ['nullable', 'numeric', 'min:0'],
            'pallet_quantity' => ['nullable', 'numeric', 'min:0'],
            'min_transport_quantity' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'numeric', 'min:0'],
            'safety_days' => ['nullable', 'numeric', 'min:0'],
            'order_enabled' => ['boolean'],
        ]);

        if (! $this->hasProduct($context, $row['sku'] ?? null)) {
            $errors[] = 'Unknown SKU ['.($row['sku'] ?? '').'].';
        }

        if (! $this->hasSupplier($context, $row['supplier_code'] ?? null)) {
            $errors[] = 'Unknown supplier ['.($row['supplier_code'] ?? '').'].';
        }

        return $errors;
    }
}
