<?php

namespace App\Services\AI;

use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiEmailExtractionReviewService
{
    public function __construct(
        private readonly AiEmailExtractionValidationService $validationService,
        private readonly SupplierConfirmationFromAiExtractionService $supplierConfirmationService,
        private readonly CarrierQuoteFromAiExtractionService $carrierQuoteService,
    ) {}

    /**
     * @return array{validation:array<string,mixed>,applied:?Model}
     */
    public function accept(AiEmailExtraction $extraction, User $user): array
    {
        return DB::transaction(function () use ($extraction, $user): array {
            $extraction->refresh();
            $validation = $this->validationService->validate($extraction);

            if ($validation['status'] === 'rejected') {
                throw ValidationException::withMessages([
                    'extraction' => 'AI extraction shape is invalid and cannot be accepted.',
                ]);
            }

            $applied = $this->applyAcceptedOutput($extraction);
            $oldValues = $this->auditValues($extraction);

            $extraction->forceFill([
                'requires_human_review' => false,
                'review_reason' => null,
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
                'accepted_at' => now(),
                'rejected_at' => null,
            ])->save();

            $this->writeAuditLog(
                eventType: 'ai_email_extraction.accepted',
                extraction: $extraction,
                user: $user,
                oldValues: $oldValues,
                newValues: [
                    'extraction' => $this->auditValues($extraction),
                    'validation' => $validation,
                    'applied_model_type' => $applied instanceof Model ? $applied::class : null,
                    'applied_model_id' => $applied?->id,
                ],
            );

            return [
                'validation' => $validation,
                'applied' => $applied,
            ];
        });
    }

    public function reject(AiEmailExtraction $extraction, User $user): AiEmailExtraction
    {
        return DB::transaction(function () use ($extraction, $user): AiEmailExtraction {
            $oldValues = $this->auditValues($extraction);

            $extraction->forceFill([
                'requires_human_review' => false,
                'review_reason' => 'rejected_by_user',
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
                'accepted_at' => null,
                'rejected_at' => now(),
            ])->save();

            $this->writeAuditLog('ai_email_extraction.rejected', $extraction, $user, $oldValues, $this->auditValues($extraction));

            return $extraction;
        });
    }

    public function requestHumanReview(AiEmailExtraction $extraction, User $user): AiEmailExtraction
    {
        return DB::transaction(function () use ($extraction, $user): AiEmailExtraction {
            $oldValues = $this->auditValues($extraction);

            $extraction->forceFill([
                'requires_human_review' => true,
                'review_reason' => 'human_review_requested',
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
            ])->save();

            $this->writeAuditLog('ai_email_extraction.human_review_requested', $extraction, $user, $oldValues, $this->auditValues($extraction));

            return $extraction;
        });
    }

    private function applyAcceptedOutput(AiEmailExtraction $extraction): ?Model
    {
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];

        return match ($output['email_type'] ?? 'unclear') {
            'supplier_confirmation', 'date_update', 'quantity_mismatch' => $this->supplierConfirmationService->create($extraction),
            'transport_quote' => $this->carrierQuoteService->create($extraction),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(AiEmailExtraction $extraction): array
    {
        return $extraction->only([
            'requires_human_review',
            'review_reason',
            'reviewed_by_user_id',
            'reviewed_at',
            'accepted_at',
            'rejected_at',
        ]);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function writeAuditLog(
        string $eventType,
        AiEmailExtraction $extraction,
        User $user,
        array $oldValues,
        array $newValues,
    ): void {
        $extraction->loadMissing('emailMessage:id,company_id');

        AuditLog::query()->create([
            'company_id' => $extraction->emailMessage?->company_id,
            'user_id' => $user->id,
            'event_type' => $eventType,
            'auditable_type' => $extraction::class,
            'auditable_id' => $extraction->id,
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => [],
            'created_at' => now(),
        ]);
    }
}
