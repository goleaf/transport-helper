<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RunPilotCheckRequest;
use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotReadinessService;
use Illuminate\Http\RedirectResponse;

class PilotReadinessController extends Controller
{
    public function store(RunPilotCheckRequest $request, PilotSupplier $pilot, PilotReadinessService $service): RedirectResponse
    {
        $service->check($pilot, $request->user());

        return back()->with('status', 'Pilot readiness check completed.');
    }
}
