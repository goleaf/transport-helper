<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ApplyAutofillRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSupplyWorkflow() ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'confirm_apply' => ['sometimes', 'accepted'],
        ];
    }
}
