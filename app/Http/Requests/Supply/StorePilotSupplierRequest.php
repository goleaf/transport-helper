<?php

namespace App\Http\Requests\Supply;

use App\Models\PilotSupplier;
use Illuminate\Foundation\Http\FormRequest;

class StorePilotSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PilotSupplier::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'allow_multiple' => ['nullable', 'boolean'],
        ];
    }
}
