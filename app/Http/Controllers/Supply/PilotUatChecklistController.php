<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UpdatePilotUatChecklistRequest;
use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotUatChecklistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PilotUatChecklistController extends Controller
{
    public function show(PilotSupplier $pilot, PilotUatChecklistService $service): View
    {
        Gate::authorize('view', $pilot);

        return view('supply.pilots.uat', [
            'pilot' => $pilot->load(['supplier:id,name', 'company:id,name']),
            'checklist' => $service->getChecklist($pilot),
            'evaluation' => $service->evaluate($pilot),
        ]);
    }

    public function update(UpdatePilotUatChecklistRequest $request, PilotSupplier $pilot, PilotUatChecklistService $service): RedirectResponse
    {
        $service->updateChecklist($pilot, $request->validated('items'), $request->user());

        return back()->with('status', 'Pilot UAT checklist updated.');
    }
}
