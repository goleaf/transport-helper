<?php

namespace App\Services\Import\Validators;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Validators\Concerns\ValidatesNormalizedImportRows;
use Illuminate\Validation\Rule;

class ReservationValidator implements ImportValidatorInterface
{
    use ValidatesNormalizedImportRows;

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validatorErrors($row, [
            'company_id' => ['required', 'integer'],
            'sku' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reserved_at' => ['required', 'date'],
            'expected_usage_date' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(['active', 'used', 'cancelled', 'expired'])],
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

        return $this->result($errors, $warnings, $row);
    }
}
