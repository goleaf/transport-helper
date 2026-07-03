<?php

namespace App\Http\Requests\Supply;

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentSourceType;
use App\Enums\IncidentType;
use App\Models\OperationalIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', OperationalIncident::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'incident_type' => ['required', 'string', Rule::in(IncidentType::values())],
            'severity' => ['nullable', 'string', Rule::in(IncidentSeverity::values())],
            'priority' => ['nullable', 'string', Rule::in(IncidentPriority::values())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'source_type' => ['nullable', 'string', Rule::in(IncidentSourceType::values())],
            'source_id' => ['nullable', 'integer'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'metadata_json' => ['nullable', 'array'],
        ];
    }
}
