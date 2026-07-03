<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ApplicationGateSummary extends Component
{
    public string $canApplyLabel;

    public string $targetAction;

    public array $blockingReasons;

    public function __construct(mixed $gate)
    {
        $gate = is_array($gate) ? $gate : [];

        $this->canApplyLabel = ($gate['can_apply'] ?? false) ? 'Yes' : 'No';
        $this->targetAction = DisplayValue::scalar($gate['target_action'] ?? null);
        $this->blockingReasons = is_array($gate['blocking_reasons'] ?? null) ? $gate['blocking_reasons'] : [];
    }

    public function render(): View
    {
        return view('components.supply.application-gate-summary');
    }
}
