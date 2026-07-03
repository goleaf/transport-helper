<?php

namespace App\View\Components\Supply;

use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InlineList extends Component
{
    public string $text;

    public function __construct(mixed $items = [], string $empty = '')
    {
        $this->text = DisplayValue::inlineList($items, $empty);
    }

    public function render(): View
    {
        return view('components.supply.inline-list');
    }
}
