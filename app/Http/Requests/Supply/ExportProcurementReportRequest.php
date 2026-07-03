<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportProcurementReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasPermissionTo('export_analytics') || $user->hasPermissionTo('manage_settings') || $user->hasRole('admin'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(['budget_status', 'approvals', 'exceptions', 'supplier_spend'])],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ];
    }
}
