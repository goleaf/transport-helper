<?php

namespace App\View\Components\Supply;

use App\Support\SupplyNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navigation extends Component
{
    public array $items;

    public function __construct()
    {
        $this->items = collect(SupplyNavigation::items())->map(function (array $item): array {
            $isActive = request()->routeIs($item['active']);

            return [
                'label' => $item['label'],
                'href' => route($item['route']),
                'is_active' => $isActive,
                'show_children' => $isActive && ! empty($item['children']),
                'children' => collect($item['children'] ?? [])->map(fn (array $child): array => [
                    'label' => $child['label'],
                    'href' => route($child['route']).'#'.$child['fragment'],
                ])->all(),
            ];
        })->all();
    }

    public function render(): View
    {
        return view('components.supply.navigation');
    }
}
