<?php

namespace App\Http\Requests\Supply;

use App\Models\PilotSupplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePilotUatChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pilot = $this->route('pilot');

        return $pilot instanceof PilotSupplier
            && $this->user()?->can('updateUat', $pilot) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.key' => ['required', 'string'],
            'items.*.status' => ['required', 'string', Rule::in(['pending', 'passed', 'failed', 'blocked', 'not_applicable'])],
            'items.*.note' => ['nullable', 'string', 'max:5000'],
            'items.*.evidence' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
