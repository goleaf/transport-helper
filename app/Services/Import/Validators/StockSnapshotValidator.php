<?php

namespace App\Services\Import\Validators;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Validators\Concerns\ValidatesNormalizedImportRows;

class StockSnapshotValidator implements ImportValidatorInterface
{
    use ValidatesNormalizedImportRows;

    public function validate(array $row, array $context = []): array
    {
        $allowNegativeStock = (bool) ($context['allow_negative_stock'] ?? false);
        $errors = $this->validatorErrors($row, [
            'company_id' => ['required', 'integer'],
            'sku' => ['required', 'string'],
            'snapshot_date' => ['required', 'date'],
            'free_stock' => ['required', 'numeric'],
            'total_stock' => ['nullable', 'numeric'],
            'reserved_quantity' => ['nullable', 'numeric'],
            'damaged_quantity' => ['nullable', 'numeric'],
            'inactive_quantity' => ['nullable', 'numeric'],
            'in_transit_quantity' => ['nullable', 'numeric'],
        ]);
        $warnings = [];

        if (! $this->companyExists($row['company_id'] ?? null)) {
            $errors[] = 'Company not found.';
        }

        $product = $this->productForSku($context, $row['company_id'] ?? null, $row['sku'] ?? null);

        if ($product === null) {
            $errors[] = 'SKU not found: '.($row['sku'] ?? '');
        } else {
            $row['product_id'] = $product->getKey();
        }

        if (is_numeric($row['free_stock'] ?? null) && (float) $row['free_stock'] < 0) {
            if ($allowNegativeStock) {
                $warnings[] = 'negative_free_stock';
            } else {
                $errors[] = 'free_stock must not be negative.';
            }
        }

        foreach (['total_stock', 'reserved_quantity', 'damaged_quantity', 'inactive_quantity', 'in_transit_quantity'] as $field) {
            $error = $this->nonNegativeError($field, $row[$field] ?? null);

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $this->result($errors, $warnings, $row);
    }
}
