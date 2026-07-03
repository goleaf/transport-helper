<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrepareSupplierOrderEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('prepareEmail', $this->route('order')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'export_file_id' => ['nullable', 'integer', 'exists:export_files,id'],
            'auto_export' => ['nullable', 'boolean'],
            'auto_export_format' => ['nullable', 'string', Rule::in(['csv', 'json', 'excel_csv'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'body_text' => ['nullable', 'string', 'max:20000'],
            'language' => ['nullable', 'string', 'max:10'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
            'email_account_id' => ['nullable', 'integer', 'exists:email_accounts,id'],
        ];
    }
}
