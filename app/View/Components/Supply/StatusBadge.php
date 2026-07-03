<?php

namespace App\View\Components\Supply;

use App\Services\Supply\UI\SupplyStatusPresenter;
use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public string $statusValue;

    public string $label;

    public string $tone;

    public string $description;

    public function __construct(mixed $status, string $context = 'default')
    {
        $presented = app(SupplyStatusPresenter::class)->present($status, $context);

        $this->statusValue = DisplayValue::statusValue($status);
        $this->label = $presented['label'];
        $this->tone = $presented['tone'];
        $this->description = $presented['description'];
    }

    public function render(): View
    {
        return view('components.supply.status-badge');
    }
}
