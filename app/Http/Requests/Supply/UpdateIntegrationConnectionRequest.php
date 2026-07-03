<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntegrationConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('connection')) === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('config_json') && ! $this->has('config')) {
            $decoded = json_decode((string) $this->input('config_json'), true);
            $this->merge(['config' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'type' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'environment' => ['nullable', 'string', 'max:50'],
            'config' => ['nullable', 'array'],
            'is_external' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
