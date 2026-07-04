<?php

namespace App\Http\Requests\Supply;

use App\Models\ProductAlias;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductAliasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductAlias::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'alias' => ['required', 'string', 'max:255'],
            'alias_type' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
