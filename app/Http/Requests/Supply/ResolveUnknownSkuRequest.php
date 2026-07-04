<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveUnknownSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('resolution')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'resolution_type' => ['required', 'string', Rule::in(['existing_product', 'product_alias', 'product_change_request', 'ignored'])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'alias_type' => ['nullable', 'string', 'max:255'],
            'requested_changes_json' => ['nullable', 'array'],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
