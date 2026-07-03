<?php

namespace App\Http\Requests\Supply;

use App\Enums\CorrectiveActionStatus;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentCorrectiveActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident instanceof OperationalIncident && $this->user()?->can('create', [IncidentCorrectiveAction::class, $incident]) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(CorrectiveActionStatus::values())],
        ];
    }
}
