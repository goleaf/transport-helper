<?php

namespace App\Http\Requests\Supply;

use App\Models\MasterDataChangeRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMasterDataChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MasterDataChangeRequest::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'request_type' => ['required', 'string', Rule::in(['create_product', 'update_product', 'create_supplier', 'update_supplier', 'create_alias', 'update_alias', 'supplier_product_mapping', 'lifecycle_change', 'merge_request', 'other'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'pending_approval'])],
            'related_model_type' => ['nullable', 'string', 'max:255'],
            'related_model_id' => ['nullable', 'integer'],
            'requested_changes_json' => ['nullable', 'array'],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
