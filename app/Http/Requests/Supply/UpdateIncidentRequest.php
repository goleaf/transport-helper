<?php

namespace App\Http\Requests\Supply;

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Models\OperationalIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident instanceof OperationalIncident && $this->user()?->can('update', $incident) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'severity' => ['nullable', 'string', Rule::in(IncidentSeverity::values())],
            'priority' => ['nullable', 'string', Rule::in(IncidentPriority::values())],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'metadata_json' => ['nullable', 'array'],
        ];
    }
}
