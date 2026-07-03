<?php

namespace App\Http\Requests\Supply;

use App\Models\ManufacturerFormTemplateFile;
use Illuminate\Foundation\Http\FormRequest;

class PreviewManufacturerFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('preview', ManufacturerFormTemplateFile::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_order_id' => ['required', 'integer', 'exists:supplier_orders,id'],
        ];
    }
}
