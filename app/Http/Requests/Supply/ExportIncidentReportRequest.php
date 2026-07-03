<?php

namespace App\Http\Requests\Supply;

use App\Models\OperationalIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('export', OperationalIncident::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(['csv', 'json'])],
            'filters' => ['nullable', 'array'],
        ];
    }
}
