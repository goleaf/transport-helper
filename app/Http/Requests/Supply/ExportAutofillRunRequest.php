<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportAutofillRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSupplyWorkflow() ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(['json', 'csv', 'internal_html', 'excel', 'pdf', 'custom'])],
        ];
    }
}
