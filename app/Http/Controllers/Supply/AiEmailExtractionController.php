<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\AiEmailExtraction;
use App\Services\AI\Email\AiEmailExtractionValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class AiEmailExtractionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AiEmailExtraction::class);

        $extractions = AiEmailExtraction::query()
            ->select([
                'id',
                'email_message_id',
                'provider',
                'model',
                'prompt_version',
                'confidence',
                'requires_human_review',
                'review_reason',
                'accepted_at',
                'rejected_at',
                'created_at',
            ])
            ->with(['emailMessage:id,subject,from_email,related_supplier_order_id', 'emailMessage.relatedSupplierOrder:id,order_number'])
            ->when(request('provider'), fn ($query, string $provider) => $query->where('provider', $provider))
            ->when(request('requires_human_review') !== null, fn ($query) => $query->where('requires_human_review', (bool) request('requires_human_review')))
            ->when(request()->boolean('accepted'), fn ($query) => $query->whereNotNull('accepted_at'))
            ->when(request()->boolean('rejected'), fn ($query) => $query->whereNotNull('rejected_at'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.ai-extractions.index', [
            'extractions' => $extractions,
        ]);
    }

    public function show(AiEmailExtraction $extraction, AiEmailExtractionValidationService $validationService): View
    {
        Gate::authorize('view', $extraction);

        $extraction->load([
            'emailMessage:id,company_id,subject,from_email,body_text,related_supplier_id,related_supplier_order_id',
            'emailMessage.relatedSupplier:id,name',
            'emailMessage.relatedSupplierOrder:id,order_number',
            'reviewedBy:id,name',
        ]);

        $validation = $validationService->validate(is_array($extraction->output_json) ? $extraction->output_json : [], [
            'supplier' => $extraction->emailMessage?->relatedSupplier,
            'supplier_order' => $extraction->emailMessage?->relatedSupplierOrder,
        ]);
        $validation['confidence'] = $validation['normalized_output']['confidence'] ?? $extraction->confidence ?? 0;
        $validation['reasons'] = array_values(array_unique(array_merge($validation['errors'] ?? [], $validation['warnings'] ?? [])));

        return view('supply.ai-extractions.show', [
            'extraction' => $extraction,
            'validation' => $validation,
            'canAccept' => Gate::allows('accept', $extraction),
            'canReject' => Gate::allows('reject', $extraction),
            'canRequestHumanReview' => Gate::allows('markNeedsReview', $extraction),
            'canMarkNeedsReview' => Gate::allows('markNeedsReview', $extraction),
            'canApplySupplierConfirmation' => Gate::allows('applyAsSupplierConfirmation', $extraction),
            'canApplyCarrierQuote' => Gate::allows('applyAsCarrierQuote', $extraction),
        ]);
    }
}
