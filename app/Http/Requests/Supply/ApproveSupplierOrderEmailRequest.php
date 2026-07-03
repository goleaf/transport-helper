<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ApproveSupplierOrderEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approveEmail', $this->route('order')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'confirm_no_attachment' => ['nullable', 'boolean'],
            'approval_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
