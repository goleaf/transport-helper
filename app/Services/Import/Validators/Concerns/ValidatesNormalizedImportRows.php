<?php

namespace App\Services\Import\Validators\Concerns;

use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;

trait ValidatesNormalizedImportRows
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $rules
     * @return list<string>
     */
    private function validatorErrors(array $row, array $rules): array
    {
        return Validator::make($row, $rules)->errors()->all();
    }

    private function companyExists(mixed $companyId): bool
    {
        return is_numeric($companyId)
            && Company::query()->whereKey((int) $companyId)->exists();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function productForSku(array $context, mixed $companyId, mixed $sku): ?Product
    {
        if (! is_numeric($companyId) || ! is_string($sku) || trim($sku) === '') {
            return null;
        }

        $key = strtoupper($sku);
        $fromContext = $context['products_by_sku'][$key] ?? null;

        if ($fromContext instanceof Product) {
            return $fromContext;
        }

        return Product::query()
            ->select(['id', 'company_id', 'sku'])
            ->where('company_id', (int) $companyId)
            ->where('sku', $key)
            ->first();
    }

    private function supplierExists(mixed $supplierId): bool
    {
        return is_numeric($supplierId)
            && Supplier::query()->whereKey((int) $supplierId)->exists();
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  array<string, mixed>  $normalized
     * @return array{valid:bool,errors:list<string>,warnings:list<string>,normalized:array<string,mixed>}
     */
    private function result(array $errors, array $warnings, array $normalized): array
    {
        return [
            'valid' => $errors === [],
            'errors' => array_values($errors),
            'warnings' => array_values($warnings),
            'normalized' => $normalized,
        ];
    }

    private function nonNegativeError(string $field, mixed $value): ?string
    {
        return is_numeric($value) && (float) $value < 0
            ? "{$field} must not be negative."
            : null;
    }

    private function positiveError(string $field, mixed $value): ?string
    {
        return $value !== null && is_numeric($value) && (float) $value <= 0
            ? "{$field} must be greater than zero."
            : null;
    }
}
