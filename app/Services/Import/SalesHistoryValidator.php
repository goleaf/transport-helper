<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Concerns\ValidatesImportValues;

class SalesHistoryValidator implements ImportValidatorInterface
{
    use ValidatesImportValues;

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validationErrors($row, [
            'sku' => ['required', 'string'],
            'sales_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'channel' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'is_promotion' => ['boolean'],
            'is_anomaly' => ['boolean'],
        ]);

        if (! $this->hasProduct($context, $row['sku'] ?? null)) {
            $errors[] = 'Unknown SKU ['.($row['sku'] ?? '').'].';
        }

        return $errors;
    }
}
