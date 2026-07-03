<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class RejectCarrierQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageLogisticsWorkflow() ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
