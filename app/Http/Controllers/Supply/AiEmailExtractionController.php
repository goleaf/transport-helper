<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\AiEmailExtraction;
use App\Services\AI\AiEmailExtractionReviewService;
use App\Services\AI\AiEmailExtractionValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AiEmailExtractionController extends Controller
{
    public function show(AiEmailExtraction $extraction, AiEmailExtractionValidationService $validationService): View
    {
        Gate::authorize('view', $extraction);

        $extraction->load([
            'emailMessage:id,company_id,subject,from_email,related_supplier_order_id',
            'emailMessage.relatedSupplierOrder:id,order_number',
            'reviewedBy:id,name',
        ]);

        return view('supply.ai-extractions.show', [
            'extraction' => $extraction,
            'validation' => $validationService->validate($extraction),
            'canAccept' => Gate::allows('accept', $extraction),
            'canReject' => Gate::allows('reject', $extraction),
            'canRequestHumanReview' => Gate::allows('requestHumanReview', $extraction),
        ]);
    }

    public function accept(Request $request, AiEmailExtraction $extraction, AiEmailExtractionReviewService $reviewService): RedirectResponse
    {
        Gate::authorize('accept', $extraction);

        $reviewService->accept($extraction, $request->user());

        return redirect()
            ->route('supply.ai-extractions.show', $extraction)
            ->with('status', 'AI extraction accepted.');
    }

    public function reject(Request $request, AiEmailExtraction $extraction, AiEmailExtractionReviewService $reviewService): RedirectResponse
    {
        Gate::authorize('reject', $extraction);

        $reviewService->reject($extraction, $request->user());

        return redirect()
            ->route('supply.ai-extractions.show', $extraction)
            ->with('status', 'AI extraction rejected.');
    }

    public function requestHumanReview(Request $request, AiEmailExtraction $extraction, AiEmailExtractionReviewService $reviewService): RedirectResponse
    {
        Gate::authorize('requestHumanReview', $extraction);

        $reviewService->requestHumanReview($extraction, $request->user());

        return redirect()
            ->route('supply.ai-extractions.show', $extraction)
            ->with('status', 'AI extraction marked for human review.');
    }
}
