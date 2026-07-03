<?php

namespace App\View\Components\Supply;

use App\Services\Supply\UI\SupplyNavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navigation extends Component
{
    public array $groups;

    public function __construct(SupplyNavigationService $navigationService)
    {
        $this->groups = $navigationService->navigation(request()->user());
    }

    public function render(): View
    {
        return view('components.supply.navigation');
    }
}
