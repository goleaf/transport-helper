<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class TestIntegrationConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('test', $this->route('connection')) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dry_run' => ['nullable', 'boolean'],
            'allow_real_call' => ['nullable', 'boolean'],
            'test_type' => ['nullable', 'string', 'max:100'],
            'confirmation' => ['nullable', 'boolean'],
        ];
    }
}
