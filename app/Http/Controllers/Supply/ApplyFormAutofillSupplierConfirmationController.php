<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApplyFormAutofillSupplierConfirmationRequest;
use App\Models\FormAutofillRun;
use App\Services\Supply\Confirmations\SupplierConfirmationFromFormAutofillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ApplyFormAutofillSupplierConfirmationController extends Controller
{
    public function store(ApplyFormAutofillSupplierConfirmationRequest $request, FormAutofillRun $run, SupplierConfirmationFromFormAutofillService $service): RedirectResponse
    {
        Gate::authorize('applyAsSupplierConfirmation', $run);

        $result = $service->apply($run, $request->user(), $request->validated());

        return redirect()
            ->route('supply.supplier-confirmations.show', $result['confirmation'])
            ->with('status', 'Form autofill run applied as supplier confirmation.');
    }
}
