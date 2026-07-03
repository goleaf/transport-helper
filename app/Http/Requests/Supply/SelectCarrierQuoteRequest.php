<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class SelectCarrierQuoteRequest extends FormRequest
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
            'confirm_selection' => ['sometimes', 'accepted'],
        ];
    }
}
