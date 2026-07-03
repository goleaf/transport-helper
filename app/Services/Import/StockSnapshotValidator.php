<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Concerns\ValidatesImportValues;

class StockSnapshotValidator implements ImportValidatorInterface
{
    use ValidatesImportValues;

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validationErrors($row, [
            'sku' => ['required', 'string'],
            'snapshot_date' => ['required', 'date'],
            'free_stock' => ['required', 'numeric', 'min:0'],
            'total_stock' => ['nullable', 'numeric', 'min:0'],
            'reserved_quantity' => ['nullable', 'numeric', 'min:0'],
            'damaged_quantity' => ['nullable', 'numeric', 'min:0'],
            'inactive_quantity' => ['nullable', 'numeric', 'min:0'],
            'in_transit_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! $this->hasProduct($context, $row['sku'] ?? null)) {
            $errors[] = 'Unknown SKU ['.($row['sku'] ?? '').'].';
        }

        return $errors;
    }
}
