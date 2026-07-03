<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ReviewAiEmailExtractionRequest;
use App\Models\AiEmailExtraction;
use App\Services\AI\Email\AiEmailExtractionReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AiEmailExtractionReviewController extends Controller
{
    public function store(ReviewAiEmailExtractionRequest $request, AiEmailExtraction $extraction, AiEmailExtractionReviewService $reviewService): RedirectResponse
    {
        $validated = $request->validated();

        match ($validated['decision']) {
            'accept' => Gate::authorize('accept', $extraction),
            'reject' => Gate::authorize('reject', $extraction),
            'needs_review' => Gate::authorize('markNeedsReview', $extraction),
        };

        match ($validated['decision']) {
            'accept' => $reviewService->accept($extraction, $request->user(), $validated),
            'reject' => $reviewService->reject($extraction, $request->user(), $validated),
            'needs_review' => $reviewService->markNeedsReview($extraction, $request->user(), $validated),
        };

        return redirect()
            ->route('supply.ai-extractions.show', $extraction)
            ->with('status', 'AI extraction review saved.');
    }
}
