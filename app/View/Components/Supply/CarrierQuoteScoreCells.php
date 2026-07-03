<?php

namespace App\View\Components\Supply;

use App\Models\CarrierQuote;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CarrierQuoteScoreCells extends Component
{
    public array $explanation;

    public string $warnings;

    public function __construct(CarrierQuote $quote)
    {
        $this->explanation = is_array($quote->score_explanation_json) ? $quote->score_explanation_json : [];
        $warnings = $this->explanation['warnings'] ?? [];
        $this->warnings = is_array($warnings) ? implode(', ', $warnings) : '';
    }

    public function render(): View
    {
        return view('components.supply.carrier-quote-score-cells');
    }
}
