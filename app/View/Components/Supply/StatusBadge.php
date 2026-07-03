<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public string $statusValue;

    public string $label;

    public function __construct(mixed $status)
    {
        $this->statusValue = DisplayValue::statusValue($status);
        $this->label = str_replace('_', ' ', $this->statusValue);
    }

    public function render(): View
    {
        return view('components.supply.status-badge');
    }
}
