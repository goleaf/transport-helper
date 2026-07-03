<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Concerns\ValidatesImportValues;

class InboundOrderValidator implements ImportValidatorInterface
{
    use ValidatesImportValues;

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validationErrors($row, [
            'supplier_code' => ['required', 'string'],
            'sku' => ['required', 'string'],
            'ordered_quantity' => ['required', 'numeric', 'min:0'],
            'confirmed_quantity' => ['nullable', 'numeric', 'min:0'],
            'expected_arrival_date' => ['nullable', 'date'],
            'confirmed_arrival_date' => ['nullable', 'date'],
            'status' => ['required', 'string'],
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
