<?php

namespace App\Http\Requests\Supply;

use App\Models\TrendOverride;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrendOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $override = $this->route('override');

        return $override instanceof TrendOverride
            ? $user->can('update', $override)
            : $user->can('create', TrendOverride::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'trend_value' => ['required', 'numeric', 'min:0.0001'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
