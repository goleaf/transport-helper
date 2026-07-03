<?php

namespace App\Http\Requests\Supply;

use App\Enums\IncidentStatus;
use App\Models\OperationalIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeIncidentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident instanceof OperationalIncident && $this->user()?->can('changeStatus', $incident) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(IncidentStatus::values())],
            'resolution_note' => ['nullable', 'string', 'max:10000'],
            'no_action_required_reason' => ['nullable', 'string', 'max:10000'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
