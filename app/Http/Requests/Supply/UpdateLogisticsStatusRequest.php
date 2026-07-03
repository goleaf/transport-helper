<?php

namespace App\Http\Requests\Supply;

use App\Enums\LogisticsStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLogisticsStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateStatus', $this->route('record')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_column(LogisticsStatus::cases(), 'value'))],
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
            'override' => ['nullable', 'boolean'],
        ];
    }
}
