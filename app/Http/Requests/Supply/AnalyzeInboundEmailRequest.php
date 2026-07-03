<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeInboundEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasPermission('review_ai_extractions') || $user->hasRole('admin') || $user->hasRole('supply_manager'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'analyzer' => ['nullable', 'string', 'in:fake,rule_based,external'],
            'force' => ['nullable', 'boolean'],
            'sync' => ['nullable', 'boolean'],
            'fake_output' => ['nullable', 'array'],
        ];
    }
}
