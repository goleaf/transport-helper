<?php

namespace App\Http\Requests\Supply;

use App\Models\MasterDataMergeProposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMergeProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MasterDataMergeProposal::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'merge_type' => ['required', 'string', Rule::in(['product', 'supplier'])],
            'source_id' => ['required', 'integer', 'different:target_id'],
            'target_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
