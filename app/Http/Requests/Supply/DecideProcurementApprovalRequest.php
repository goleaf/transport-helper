<?php

namespace App\Http\Requests\Supply;

use App\Models\ProcurementApprovalRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideProcurementApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $approvalRequest = $this->route('approvalRequest');

        return $approvalRequest instanceof ProcurementApprovalRequest && ($this->user()?->can('decide', $approvalRequest) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:5000'],
            'reason' => [Rule::requiredIf($this->routeIs('supply.procurement.approvals.reject')), 'nullable', 'string', 'min:3', 'max:5000'],
        ];
    }
}
