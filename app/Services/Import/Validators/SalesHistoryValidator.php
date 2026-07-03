<?php

namespace App\Services\Import\Validators;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Validators\Concerns\ValidatesNormalizedImportRows;

class SalesHistoryValidator implements ImportValidatorInterface
{
    use ValidatesNormalizedImportRows;

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validatorErrors($row, [
            'company_id' => ['required', 'integer'],
            'sku' => ['required', 'string'],
            'sales_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'channel' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'is_promotion' => ['boolean'],
            'is_anomaly' => ['boolean'],
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

        if (($row['quantity'] ?? null) === 0.0) {
            $warnings[] = 'quantity_is_zero';
        }

        if (($row['is_promotion'] ?? false) === true) {
            $warnings[] = 'promotion_row';
        }

        if (($row['is_anomaly'] ?? false) === true) {
            $warnings[] = 'anomaly_row';
        }

        return $this->result($errors, $warnings, $row);
    }
}
