<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('manage_products') || $this->user()?->hasRole('admin') || false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['draft', 'active', 'blocked', 'discontinued', 'replaced', 'merged', 'archived'])],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
            'replaced_by_product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
