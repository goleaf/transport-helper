<?php

namespace App\Http\Requests\Supply;

use App\Models\ProcurementApprovalRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestProcurementApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProcurementApprovalRequest::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'approvable_type' => ['required', 'string', Rule::in(['proposal', 'supplier_order', 'scenario'])],
            'approvable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
