<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ApproveIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connection = $this->route('connection');

        return $this->user()?->can($this->route()?->getName() === 'supply.integrations.activate' ? 'activate' : 'approve', $connection) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:5000'],
            'override_activation' => ['nullable', 'boolean'],
        ];
    }
}
