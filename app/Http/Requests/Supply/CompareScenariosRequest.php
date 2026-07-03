<?php

namespace App\Http\Requests\Supply;

use App\Models\CalculationScenario;
use Illuminate\Foundation\Http\FormRequest;

class CompareScenariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compare', CalculationScenario::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'scenario_a_id' => ['required', 'integer', 'exists:calculation_scenarios,id'],
            'scenario_b_id' => ['required', 'integer', 'exists:calculation_scenarios,id'],
        ];
    }
}
