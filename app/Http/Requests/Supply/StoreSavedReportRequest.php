<?php

namespace App\Http\Requests\Supply;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasRole('admin') || $user->hasPermissionTo('manage_saved_reports'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'report_type' => ['required', 'string', Rule::in(ReportType::values())],
            'filters_json' => ['nullable', 'array'],
            'columns_json' => ['nullable', 'array'],
            'chart_config_json' => ['nullable', 'array'],
            'is_shared' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ];
    }
}
