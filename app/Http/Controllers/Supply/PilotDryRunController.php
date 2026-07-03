<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RunPilotCheckRequest;
use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotDryRunService;
use Illuminate\Http\RedirectResponse;

class PilotDryRunController extends Controller
{
    public function store(RunPilotCheckRequest $request, PilotSupplier $pilot, string $runType, PilotDryRunService $service): RedirectResponse
    {
        $service->runByType($pilot, $runType, $request->user());

        return back()->with('status', 'Pilot dry-run completed.');
    }
}
