<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ManualInboundEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->hasPermission('review_ai_extractions')
            || $user->hasPermission('approve_supplier_emails')
            || $user->hasRole('admin')
            || $user->hasRole('supply_manager')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'email_account_id' => ['nullable', 'exists:email_accounts,id'],
            'from_email' => ['required', 'email'],
            'to' => ['nullable', 'array'],
            'to.*' => ['email'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
            'subject' => ['nullable', 'string', 'max:500'],
            'body_text' => ['nullable', 'string'],
            'body_html' => ['nullable', 'string'],
            'received_at' => ['nullable', 'date'],
            'message_id' => ['nullable', 'string', 'max:255'],
            'thread_id' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
            'analyze' => ['nullable', 'boolean'],
            'analyzer' => ['nullable', 'string', 'in:fake,rule_based,external'],
            'sync_analysis' => ['nullable', 'boolean'],
        ];
    }
}
