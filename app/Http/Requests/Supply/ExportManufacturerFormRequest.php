<?php

namespace App\Http\Requests\Supply;

use App\Models\ManufacturerFormTemplateFile;
use Illuminate\Foundation\Http\FormRequest;

class ExportManufacturerFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('export', ManufacturerFormTemplateFile::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'form_template_id' => ['required', 'integer', 'exists:form_templates,id'],
            'format' => ['nullable', 'string', 'max:50'],
            'portal_url' => ['nullable', 'url', 'max:2000'],
        ];
    }
}
