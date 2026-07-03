<?php

namespace App\View\Components\Supply;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class T0T3Timeline extends Component
{
    public array $items;

    public function __construct(
        string $t0 = '',
        string $t1 = '',
        string $t2 = '',
        string $t3 = '',
    ) {
        $this->items = [
            ['label' => 'T0', 'title' => 'Today / order date', 'value' => $t0],
            ['label' => 'T1', 'title' => 'Expected goods arrival', 'value' => $t1],
            ['label' => 'T2', 'title' => 'End of planned coverage', 'value' => $t2],
            ['label' => 'T3', 'title' => 'End of safety horizon', 'value' => $t3],
        ];
    }

    public function render(): View
    {
        return view('components.supply.t0-t3-timeline');
    }
}
