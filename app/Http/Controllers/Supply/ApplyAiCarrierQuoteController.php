<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApplyAiCarrierQuoteRequest;
use App\Models\AiEmailExtraction;
use App\Services\Supply\Transport\CarrierQuoteFromAiExtractionService;
use Illuminate\Http\RedirectResponse;

class ApplyAiCarrierQuoteController extends Controller
{
    public function store(
        ApplyAiCarrierQuoteRequest $request,
        AiEmailExtraction $extraction,
        CarrierQuoteFromAiExtractionService $service,
    ): RedirectResponse {
        $result = $service->apply($extraction, $request->user(), $request->validated());

        return redirect()
            ->route('supply.transport.quotes.show', $result['quote'])
            ->with('status', 'Carrier quote candidate created from AI extraction.');
    }
}
