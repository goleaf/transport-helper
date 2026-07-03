<?php

namespace App\Http\Requests\Supply;

use App\Models\ManufacturerFormTemplateFile;
use Illuminate\Foundation\Http\FormRequest;

class SaveManufacturerFormMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateMapping', ManufacturerFormTemplateFile::class) === true;
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
            'validation_rules' => ['nullable', 'array'],
        ];
    }
}
