<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Services\Supply\SupplyDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SupplyDashboardController extends Controller
{
    public function __invoke(Request $request, SupplyDashboardService $dashboardService): View
    {
        abort_unless($request->user(), 403);

        return view('supply.dashboard', $dashboardService->data($request->user()));
    }
}
