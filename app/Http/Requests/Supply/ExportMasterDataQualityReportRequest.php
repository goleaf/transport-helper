<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportMasterDataQualityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('export_analytics')
            || $this->user()?->hasPermissionTo('manage_products')
            || $this->user()?->hasRole('admin')
            || false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'format' => ['required', 'string', Rule::in(['csv'])],
        ];
    }
}
