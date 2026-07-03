<?php

namespace App\Http\Requests\Supply;

use App\Enums\CorrectiveActionStatus;
use App\Models\IncidentCorrectiveAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentCorrectiveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $action = $this->route('action');

        return $action instanceof IncidentCorrectiveAction && $this->user()?->can('update', $action) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(CorrectiveActionStatus::values())],
            'completion_note' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
