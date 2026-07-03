<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormulaExplanation extends Component
{
    public array $steps;

    public string $finalResult;

    public bool $hasSteps;

    public function __construct(mixed $explanation = [])
    {
        $explanation = is_array($explanation) ? $explanation : [];
        $this->steps = collect($explanation['formula_steps'] ?? [])
            ->map(fn (mixed $step): array => $this->prepareStep($step))
            ->values()
            ->all();
        $this->hasSteps = $this->steps !== [];
        $this->finalResult = DisplayValue::scalar($explanation['final_result'] ?? $explanation['recommended_quantity'] ?? null, 'Not calculated');
    }

    public function render(): View
    {
        return view('components.supply.formula-explanation');
    }

    private function prepareStep(mixed $step): array
    {
        $step = is_array($step) ? $step : ['name' => 'Formula step', 'calculation' => $step];

        return [
            'name' => DisplayValue::scalar($step['name'] ?? 'Formula step'),
            'formula' => DisplayValue::scalar($step['formula'] ?? ''),
            'calculation' => DisplayValue::scalar($step['calculation'] ?? ''),
            'value' => DisplayValue::scalar($step['value'] ?? ''),
        ];
    }
}
