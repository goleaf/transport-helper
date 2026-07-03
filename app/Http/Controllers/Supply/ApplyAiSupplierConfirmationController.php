<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApplyAiSupplierConfirmationRequest;
use App\Models\AiEmailExtraction;
use App\Services\Supply\Confirmations\SupplierConfirmationFromAiExtractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ApplyAiSupplierConfirmationController extends Controller
{
    public function store(ApplyAiSupplierConfirmationRequest $request, AiEmailExtraction $extraction, SupplierConfirmationFromAiExtractionService $service): RedirectResponse
    {
        Gate::authorize('applyAsSupplierConfirmation', $extraction);

        $result = $service->apply($extraction, $request->user(), $request->validated());

        return redirect()
            ->route('supply.supplier-confirmations.show', $result['confirmation'])
            ->with('status', 'AI extraction applied as supplier confirmation.');
    }
}
