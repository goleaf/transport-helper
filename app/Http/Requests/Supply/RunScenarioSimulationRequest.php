<?php

namespace App\Http\Requests\Supply;

use App\Models\CalculationScenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RunScenarioSimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('simulate', CalculationScenario::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'base_calculation_run_id' => ['nullable', 'integer', 'exists:calculation_runs,id'],
            'name' => ['required', 'string', 'max:255'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category' => ['nullable', 'string', 'max:255'],
            't0_date' => ['required', 'date'],
            't1_date' => ['required', 'date'],
            't2_date' => ['required', 'date'],
            't3_date' => ['required', 'date'],
            'scenario_options' => ['nullable', 'array'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $dates = collect(['t0_date', 't1_date', 't2_date', 't3_date'])
                    ->mapWithKeys(fn (string $key): array => [$key => strtotime((string) $this->input($key))]);

                if ($dates->contains(false)) {
                    return;
                }

                if (! ($dates['t0_date'] <= $dates['t1_date'] && $dates['t1_date'] <= $dates['t2_date'] && $dates['t2_date'] <= $dates['t3_date'])) {
                    $validator->errors()->add('t0_date', 'The scenario dates must be ordered T0 <= T1 <= T2 <= T3.');
                }
            },
        ];
    }
}
