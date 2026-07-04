<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteMergeProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('execute', $this->route('proposal')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'accepted'],
        ];
    }
}
