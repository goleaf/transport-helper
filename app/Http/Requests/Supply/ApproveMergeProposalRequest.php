<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMergeProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve', $this->route('proposal')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:10000'],
            'reason' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
