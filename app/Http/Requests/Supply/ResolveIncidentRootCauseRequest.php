<?php

namespace App\Http\Requests\Supply;

use App\Enums\RootCauseCategory;
use App\Models\OperationalIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveIncidentRootCauseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident instanceof OperationalIncident && $this->user()?->can('resolve', $incident) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'root_cause_category' => ['required', 'string', Rule::in(RootCauseCategory::values())],
            'root_cause_summary' => ['required', 'string', 'max:10000'],
            'prevention_notes' => ['nullable', 'string', 'max:10000'],
            'corrective_action_required' => ['nullable', 'boolean'],
            'no_action_required_reason' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
