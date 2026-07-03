<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AiExtractionOutputSummary extends Component
{
    public array $output;

    public int $confirmedItemsCount;

    public int $questionsToSupplierCount;

    public function __construct(mixed $output = [])
    {
        $this->output = is_array($output) ? $output : [];
        $this->confirmedItemsCount = DisplayValue::itemCount($this->output['confirmed_items'] ?? []);
        $this->questionsToSupplierCount = DisplayValue::itemCount($this->output['questions_to_supplier'] ?? []);
    }

    public function render(): View
    {
        return view('components.supply.ai-extraction-output-summary');
    }
}
