<?php

namespace App\Http\Requests\Supply;

use App\Models\Company;
use App\Models\Supplier;
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
        $user = $this->user();

        if ($user === null) {
            return true;
        }

        return $user->hasPermissionTo('import_data')
            || $user->hasAnyRole(['admin', 'supply_manager']);
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
            'supplier_id' => ['nullable', 'integer', Rule::exists((new Supplier)->getTable(), 'id')],
            'import_type' => ['required', 'string', Rule::in(ImportBatchService::IMPORT_TYPES)],
            'adapter' => ['required', 'string', Rule::in(['csv', 'excel', 'google_sheets', 'api', 'manual_json', 'email_attachment'])],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'dry_run' => ['sometimes', 'boolean'],
            'delimiter' => ['nullable', 'string', 'max:5'],
            'has_header' => ['sometimes', 'boolean'],
            'date_format' => ['nullable', 'string', 'max:50'],
            'allow_duplicate' => ['sometimes', 'boolean'],
            'allow_negative_stock' => ['sometimes', 'boolean'],
            'file' => ['required_if:adapter,csv', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }
}
