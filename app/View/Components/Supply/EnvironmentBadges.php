<?php

namespace App\View\Components\Supply;

use App\Services\Supply\UI\SupplyEnvironmentBadgeService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EnvironmentBadges extends Component
{
    public array $badges;

    public function __construct(SupplyEnvironmentBadgeService $environmentBadgeService)
    {
        $this->badges = $environmentBadgeService->badges();
    }

    public function render(): View
    {
        return view('components.supply.environment-badges');
    }
}
