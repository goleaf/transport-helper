<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ApproveOrderProposalItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('approve', $this->route('item'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string', 'max:2000'],
            'confirmed_review' => ['nullable', 'boolean'],
            'force_reapprove' => ['nullable', 'boolean'],
        ];
    }
}
