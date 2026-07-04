<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class DecideMasterDataChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve', $this->route('changeRequest')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:10000'],
            'reason' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
