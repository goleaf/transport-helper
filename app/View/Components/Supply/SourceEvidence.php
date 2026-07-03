<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SourceEvidence extends Component
{
    public string $confidenceText;

    public function __construct(
        public string $field,
        public mixed $suggested = null,
        public mixed $normalized = null,
        public mixed $final = null,
        public ?float $confidence = null,
        public string $source = '',
        public string $reviewReason = '',
    ) {
        $this->suggested = DisplayValue::scalar($suggested, 'Not suggested');
        $this->normalized = DisplayValue::scalar($normalized, 'Not normalized');
        $this->final = DisplayValue::scalar($final, 'Not approved');
        $this->confidenceText = $confidence === null ? 'Unknown confidence' : (int) round($confidence * 100).'% confidence';
    }

    public function render(): View
    {
        return view('components.supply.source-evidence');
    }
}
