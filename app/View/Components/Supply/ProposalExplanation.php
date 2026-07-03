<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProposalExplanation extends Component
{
    public array $explanation;

    public array $formulaSteps;

    public array $roundingSteps;

    public mixed $inputValues;

    public bool $hasFormulaSteps;

    public bool $hasRoundingSteps;

    public bool $hasInputValues;

    public function __construct(mixed $explanation = [])
    {
        $this->explanation = is_array($explanation) ? $explanation : [];
        $this->formulaSteps = $this->prepareSteps($this->explanation['formula_steps'] ?? [], 'Step');
        $this->roundingSteps = $this->prepareSteps($this->explanation['rounding_steps'] ?? [], 'Rounding');
        $this->inputValues = $this->explanation['input_values'] ?? [];
        $this->hasFormulaSteps = $this->formulaSteps !== [];
        $this->hasRoundingSteps = $this->roundingSteps !== [];
        $this->hasInputValues = is_array($this->inputValues) && $this->inputValues !== [];
    }

    public function render(): View
    {
        return view('components.supply.proposal-explanation');
    }

    private function prepareSteps(mixed $steps, string $defaultName): array
    {
        if (! is_array($steps)) {
            return [];
        }

        return collect($steps)
            ->map(fn (mixed $step): array => $this->prepareStep($step, $defaultName))
            ->values()
            ->all();
    }

    private function prepareStep(mixed $step, string $defaultName): array
    {
        if (! is_array($step)) {
            return [
                'is_structured' => false,
                'raw' => $step,
            ];
        }

        return [
            'is_structured' => true,
            'name' => (string) ($step['name'] ?? $defaultName),
            'formula' => (string) ($step['formula'] ?? ''),
            'calculation' => (string) ($step['calculation'] ?? ''),
            'has_formula' => ! blank($step['formula'] ?? null),
            'has_calculation' => ! blank($step['calculation'] ?? null),
            'has_value' => array_key_exists('value', $step),
            'value' => $step['value'] ?? null,
        ];
    }
}
