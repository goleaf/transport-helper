<?php

namespace App\Http\Requests\Supply;

use App\Models\SupplierProductPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $price = $this->route('price');

        return $price instanceof SupplierProductPrice
            ? ($this->user()?->can('update', $price) ?? false)
            : ($this->user()?->can('create', SupplierProductPrice::class) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'currency' => ['required', 'string', 'size:3'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'archived'])],
        ];
    }
}
