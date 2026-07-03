<?php

namespace App\Http\Requests\Supply;

use App\Models\PilotSupplier;
use Illuminate\Foundation\Http\FormRequest;

class SavePilotLogisticsMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pilot = $this->route('pilot');

        return $pilot instanceof PilotSupplier
            && $this->user()?->can('update', $pilot) === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mapping_json') && ! $this->has('mapping')) {
            $decoded = json_decode((string) $this->input('mapping_json'), true);
            $this->merge(['mapping' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mapping' => ['required', 'array'],
        ];
    }
}
