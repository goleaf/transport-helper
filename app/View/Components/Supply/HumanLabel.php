<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HumanLabel extends Component
{
    public string $label;

    public function __construct(mixed $value)
    {
        $this->label = DisplayValue::humanLabel($value);
    }

    public function render(): View
    {
        return view('components.supply.human-label');
    }
}
