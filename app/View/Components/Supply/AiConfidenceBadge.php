<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AiConfidenceBadge extends Component
{
    public string $label;

    public string $percent;

    public string $tone;

    public string $description;

    public function __construct(public ?float $confidence = null)
    {
        $this->label = $this->label();
        $this->percent = $confidence === null ? 'Unknown' : (int) round($confidence * 100).'%';
        $this->tone = $this->tone();
        $this->description = 'AI suggestions are not final values.';
    }

    public function render(): View
    {
        return view('components.supply.ai-confidence-badge');
    }

    private function label(): string
    {
        if ($this->confidence === null) {
            return 'Unknown confidence';
        }

        if ($this->confidence >= 0.90) {
            return 'High confidence';
        }

        if ($this->confidence >= 0.80) {
            return 'Medium confidence';
        }

        return 'Low confidence';
    }

    private function tone(): string
    {
        if ($this->confidence === null) {
            return 'neutral';
        }

        if ($this->confidence >= 0.90) {
            return 'success';
        }

        if ($this->confidence >= 0.80) {
            return 'warning';
        }

        return 'error';
    }
}
