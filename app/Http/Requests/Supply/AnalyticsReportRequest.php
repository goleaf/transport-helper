<?php

namespace App\Http\Requests\Supply;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyticsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasRole('admin') || $user->hasPermissionTo('view_analytics'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['sometimes', 'required', 'string', Rule::in(ReportType::values())],
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'carrier_id' => ['nullable', 'integer', 'exists:carriers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'group_by' => ['nullable', 'string', 'max:100'],
            'compare_to_previous_period' => ['nullable', 'boolean'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'report_period' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:100'],
        ];
    }
}
