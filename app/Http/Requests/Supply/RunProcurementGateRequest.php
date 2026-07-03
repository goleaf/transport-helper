<?php

namespace App\Http\Requests\Supply;

use App\Models\OrderProposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunProcurementGateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', OrderProposal::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['proposal', 'supplier_order', 'scenario'])],
            'id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'string', Rule::in([
                'approve_order_proposal',
                'convert_to_supplier_order',
                'approve_supplier_email',
                'send_supplier_email',
                'create_proposal_from_scenario',
            ])],
        ];
    }
}
