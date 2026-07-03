<?php

namespace App\Http\Requests\Supply;

use App\Models\OperationalIncident;
use Illuminate\Foundation\Http\FormRequest;

class RunIncidentDetectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('runDetection', OperationalIncident::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'dry_run' => ['nullable', 'boolean'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string'],
            'max_per_type' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
