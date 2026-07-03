<?php

namespace App\Services\Import\Validators;

use App\Contracts\Import\ImportValidatorInterface;
use App\Services\Import\Validators\Concerns\ValidatesNormalizedImportRows;
use Illuminate\Validation\Rule;

class InboundOrderValidator implements ImportValidatorInterface
{
    use ValidatesNormalizedImportRows;

    /**
     * @var list<string>
     */
    private const ALLOWED_STATUSES = [
        'draft',
        'ordered',
        'confirmed',
        'partially_confirmed',
        'shipped',
        'received',
        'cancelled',
        'delayed',
    ];

    public function validate(array $row, array $context = []): array
    {
        $errors = $this->validatorErrors($row, [
            'company_id' => ['required', 'integer'],
            'supplier_id' => ['required', 'integer'],
            'order_number' => ['required', 'string'],
            'sku' => ['required', 'string'],
            'ordered_quantity' => ['required', 'numeric', 'gt:0'],
            'confirmed_quantity' => ['nullable', 'numeric', 'min:0'],
            'expected_arrival_date' => ['nullable', 'date'],
            'confirmed_arrival_date' => ['nullable', 'date'],
            'ready_date' => ['nullable', 'date'],
            'shipped_date' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(self::ALLOWED_STATUSES)],
        ]);
        $warnings = [];

        if (($row['expected_arrival_date'] ?? null) === null && ($row['confirmed_arrival_date'] ?? null) === null) {
            $errors[] = 'expected_arrival_date or confirmed_arrival_date is required.';
        }

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

        return $this->result($errors, $warnings, $row);
    }
}
