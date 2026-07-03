<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $scenario = $this->route('scenario');

        return $this->user()?->can('export', $scenario) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(['csv', 'json'])],
        ];
    }
}
