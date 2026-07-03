<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HumanReviewBanner extends Component
{
    public function __construct(
        public string $reason = 'A human decision is required before this workflow can continue.',
        public string $action = 'Review the evidence and choose the next approved action.',
        public bool $blocking = false,
    ) {}

    public function render(): View
    {
        return view('components.supply.human-review-banner');
    }
}
