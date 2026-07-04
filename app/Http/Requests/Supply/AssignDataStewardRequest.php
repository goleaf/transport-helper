<?php

namespace App\Http\Requests\Supply;

use App\Models\DataStewardAssignment;
use Illuminate\Foundation\Http\FormRequest;

class AssignDataStewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DataStewardAssignment::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'stewardship_type' => ['required', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
