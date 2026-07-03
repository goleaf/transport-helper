<?php

namespace App\Services\Import\Validators;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Validators\Concerns\ValidatesNormalizedImportRows;

class ProductRuleValidator implements ImportValidatorInterface
{
    use ValidatesNormalizedImportRows;

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validatorErrors($row, [
            'company_id' => ['required', 'integer'],
            'supplier_id' => ['required', 'integer'],
            'sku' => ['required', 'string'],
            'supplier_sku' => ['nullable', 'string'],
            'manufacturer_sku' => ['nullable', 'string'],
            'moq' => ['nullable', 'numeric', 'min:0'],
            'pack_multiple' => ['nullable', 'numeric'],
            'pallet_quantity' => ['nullable', 'numeric'],
            'min_transport_quantity' => ['nullable', 'numeric'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'safety_days' => ['nullable', 'integer', 'min:0'],
            'order_enabled' => ['boolean'],
        ]);
        $warnings = [];

        if (! $this->companyExists($row['company_id'] ?? null)) {
            $errors[] = 'Company not found.';
        }

        if (! $this->supplierExists($row['supplier_id'] ?? null)) {
            $errors[] = 'Supplier not found.';
        }

        $product = $this->productForSku($context, $row['company_id'] ?? null, $row['sku'] ?? null);

        if ($product === null) {
            $errors[] = 'SKU not found: '.($row['sku'] ?? '');
        } else {
            $row['product_id'] = $product->getKey();
        }

        foreach (['pack_multiple', 'pallet_quantity', 'min_transport_quantity'] as $field) {
            $error = $this->positiveError($field, $row[$field] ?? null);

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        if (($row['pack_multiple'] ?? null) === null) {
            $warnings[] = 'no_pack_multiple';
        }

        if (($row['lead_time_days'] ?? null) === null) {
            $warnings[] = 'no_lead_time_days';
        }

        if (($row['safety_days'] ?? null) === null) {
            $warnings[] = 'no_safety_days';
        }

        return $this->result($errors, $warnings, $row);
    }
}
