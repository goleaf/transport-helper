<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RiskBadge extends Component
{
    public string $label;

    public string $tone;

    public function __construct(public string $risk = 'unknown')
    {
        $this->label = ucfirst(str_replace(['_', '-'], ' ', strtolower($risk)));
        $this->tone = match ($risk) {
            'critical', 'high' => 'error',
            'medium' => 'warning',
            'low' => 'success',
            default => 'neutral',
        };
    }

    public function render(): View
    {
        return view('components.supply.risk-badge');
    }
}
