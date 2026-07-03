<?php

namespace App\Http\Requests\Supply;

use App\Models\ManufacturerFormTemplateFile;
use Illuminate\Foundation\Http\FormRequest;

class UploadManufacturerFormTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('upload', ManufacturerFormTemplateFile::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,pdf', 'max:'.config('supply.manufacturer_forms.max_upload_size_kb', 10240)],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'version' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
