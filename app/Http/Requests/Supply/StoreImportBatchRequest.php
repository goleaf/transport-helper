<?php

namespace App\Http\Requests\Supply;

use App\Models\Company;
use App\Services\Import\ImportBatchService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImportBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists((new Company)->getTable(), 'id')],
            'import_type' => ['required', 'string', Rule::in(ImportBatchService::IMPORT_TYPES)],
            'adapter' => ['required', 'string', Rule::in(['csv', 'excel', 'google_sheets', 'api', 'manual_json', 'email_attachment'])],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'dry_run' => ['sometimes', 'boolean'],
            'file' => ['required', 'file', 'max:10240'],
        ];
    }
}
