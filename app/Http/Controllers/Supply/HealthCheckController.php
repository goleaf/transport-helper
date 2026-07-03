<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Services\Supply\Logistics\SupplyHealthCheckService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    public function index(Request $request, SupplyHealthCheckService $service): View
    {
        abort_unless($request->user()?->hasAnyRole(['admin']) || $request->user()?->hasPermissionTo('manage_settings'), 403);

        return view('supply.health.index', [
            'result' => $service->run(['user' => $request->user()]),
        ]);
    }
}
