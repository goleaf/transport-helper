<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportSupplierOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('export', $this->route('order')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'format' => [
                'required',
                'string',
                Rule::in([
                    'csv',
                    'json',
                    'excel_csv',
                    'excel-compatible-csv',
                    'excel_compatible_csv',
                    'pdf',
                    'supplier_custom_template',
                    'supplier-custom-template',
                ]),
            ],
        ];
    }
}
