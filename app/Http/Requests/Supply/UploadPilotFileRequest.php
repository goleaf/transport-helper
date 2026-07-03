<?php

namespace App\Http\Requests\Supply;

use App\Enums\PilotFileType;
use App\Models\PilotSupplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadPilotFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pilot = $this->route('pilot');

        return $pilot instanceof PilotSupplier
            && $this->user()?->can('uploadFile', $pilot) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file_type' => ['required', 'string', Rule::in(PilotFileType::values())],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,pdf,eml,html,json', 'max:'.config('supply.pilot.max_upload_size_kb', 10240)],
            'metadata' => ['nullable', 'array'],
            'metadata.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
