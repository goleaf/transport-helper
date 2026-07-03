<?php

namespace App\Http\Requests\Supply;

use App\Enums\LogisticsStatus;
use App\Models\LogisticsRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportLogisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('export', LogisticsRecord::class) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'status' => ['nullable', 'string', Rule::in(array_column(LogisticsStatus::cases(), 'value'))],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ];
    }
}
