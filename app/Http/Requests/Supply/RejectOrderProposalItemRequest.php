<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class RejectOrderProposalItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $item = $this->route('item');

        return $user !== null && (
            $user->can('approve', $item) || $user->can('adjust', $item)
        );
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }
}
