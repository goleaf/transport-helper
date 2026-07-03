<?php

namespace App\Http\Requests\Supply;

use App\Enums\LogisticsStatus;
use App\Models\LogisticsRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportLogisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('export', LogisticsRecord::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(array_column(LogisticsStatus::cases(), 'value'))],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'carrier_id' => ['nullable', 'integer', 'exists:carriers,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'delayed_only' => ['nullable', 'boolean'],
        ];
    }
}
