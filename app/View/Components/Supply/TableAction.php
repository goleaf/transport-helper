<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TableAction extends Component
{
    public function __construct(public string $href, public string $label = 'Open') {}

    public function render(): View
    {
        return view('components.supply.table-action');
    }
}
