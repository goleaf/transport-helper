<?php

namespace App\Services\Import\Concerns;

use Illuminate\Support\Facades\Validator;

trait ValidatesImportValues
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $rules
     * @return list<string>
     */
    private function validationErrors(array $row, array $rules): array
    {
        return Validator::make($row, $rules)->errors()->all();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function hasProduct(array $context, mixed $sku): bool
    {
        $products = $context['products_by_sku'] ?? [];

        return is_string($sku) && array_key_exists(strtoupper($sku), $products);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function hasSupplier(array $context, mixed $code): bool
    {
        $suppliers = $context['suppliers_by_code'] ?? [];

        return is_string($code) && array_key_exists(strtoupper($code), $suppliers);
    }
}
