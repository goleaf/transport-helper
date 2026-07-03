<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendSupplierOrderEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sendEmail', $this->route('order')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'email_account_id' => ['nullable', 'integer', 'exists:email_accounts,id'],
            'sender' => ['nullable', 'string', Rule::in(['log', 'smtp', 'gmail', 'microsoft_graph'])],
            'resend' => ['nullable', 'boolean'],
        ];
    }
}
