<?php

namespace App\Http\Requests\Supply;

use App\Models\SupplierAlias;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierAliasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SupplierAlias::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'alias' => ['required', 'string', 'max:255'],
            'alias_type' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
