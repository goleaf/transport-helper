<?php

namespace App\Http\Requests\Supply;

use App\Models\SupplierProductIdentity;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierProductIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SupplierProductIdentity::class) ?? false;
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
            'supplier_sku' => ['nullable', 'string', 'max:255'],
            'manufacturer_sku' => ['nullable', 'string', 'max:255'],
            'supplier_product_name' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
