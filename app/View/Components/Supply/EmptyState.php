<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EmptyState extends Component
{
    public bool $hasAction;

    public function __construct(
        public string $title,
        public string $message = '',
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
    ) {
        $this->hasAction = $actionLabel !== null && $actionUrl !== null;
    }

    public function render(): View
    {
        return view('components.supply.empty-state');
    }
}
