<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Concerns\ValidatesImportValues;

class ReservationValidator implements ImportValidatorInterface
{
    use ValidatesImportValues;

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validationErrors($row, [
            'sku' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'reserved_at' => ['required', 'date'],
            'expected_usage_date' => ['nullable', 'date'],
            'status' => ['required', 'string'],
        ]);

        if (! $this->hasProduct($context, $row['sku'] ?? null)) {
            $errors[] = 'Unknown SKU ['.($row['sku'] ?? '').'].';
        }

        return $errors;
    }
}
