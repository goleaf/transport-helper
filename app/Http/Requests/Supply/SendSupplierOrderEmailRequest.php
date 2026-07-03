<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class SendSupplierOrderEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sendEmail', $this->route('order')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'no_attachment_confirmed' => ['sometimes', 'accepted'],
        ];
    }
}
