<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApplyFormAutofillCarrierQuoteRequest;
use App\Models\FormAutofillRun;
use App\Services\Supply\Transport\CarrierQuoteFromFormAutofillService;
use Illuminate\Http\RedirectResponse;

class ApplyFormAutofillCarrierQuoteController extends Controller
{
    public function store(
        ApplyFormAutofillCarrierQuoteRequest $request,
        FormAutofillRun $run,
        CarrierQuoteFromFormAutofillService $service,
    ): RedirectResponse {
        $result = $service->apply($run, $request->user(), $request->validated());

        return redirect()
            ->route('supply.transport.quotes.show', $result['quote'])
            ->with('status', 'Carrier quote candidate created from form autofill.');
    }
}
