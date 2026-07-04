<?php

namespace App\Http\Requests\Supply;

use App\Models\UnknownSkuResolution;
use Illuminate\Foundation\Http\FormRequest;

class CreateUnknownSkuResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', UnknownSkuResolution::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'unknown_sku' => ['required', 'string', 'max:255'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'metadata_json' => ['nullable', 'array'],
        ];
    }
}
