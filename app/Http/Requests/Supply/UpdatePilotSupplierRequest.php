<?php

namespace App\Http\Requests\Supply;

use App\Enums\PilotSupplierStatus;
use App\Models\PilotSupplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePilotSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pilot = $this->route('pilot');

        return $pilot instanceof PilotSupplier
            && $this->user()?->can('update', $pilot) === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('data_sources_json_text') && ! $this->has('data_sources_json')) {
            $decoded = json_decode((string) $this->input('data_sources_json_text'), true);
            $this->merge(['data_sources_json' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(PilotSupplierStatus::values())],
            'description' => ['nullable', 'string', 'max:10000'],
            'data_sources_json' => ['nullable', 'array'],
        ];
    }
}
