<?php

namespace App\Services\AI\Email;

use App\Models\AiEmailExtraction;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiEmailExtractionReviewService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{extraction:AiEmailExtraction,status:string,message:string}
     */
    public function accept(AiEmailExtraction $extraction, User $user, array $validated = []): array
    {
        return DB::transaction(function () use ($extraction, $user, $validated): array {
            $extraction->refresh();

            if ($extraction->rejected_at !== null) {
                throw ValidationException::withMessages([
                    'extraction' => 'Rejected extraction cannot be accepted without a reopen workflow.',
                ]);
            }

            $oldValues = $this->reviewValues($extraction);
            $output = $this->withReviewMetadata($extraction, 'accepted', $validated['note'] ?? null, $user);

            $extraction->forceFill([
                'output_json' => $output,
                'requires_human_review' => false,
                'review_reason' => $validated['note'] ?? null,
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
                'accepted_at' => now(),
                'rejected_at' => null,
            ])->save();

            $extraction->emailMessage?->forceFill(['status' => 'analyzed'])->save();

            $this->auditReview('ai_extraction_accepted', $extraction, $user, $oldValues, $this->reviewValues($extraction), $validated);

            return [
                'extraction' => $extraction,
                'status' => 'accepted',
                'message' => 'AI extraction accepted for later application.',
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{extraction:AiEmailExtraction,status:string,message:string}
     */
    public function reject(AiEmailExtraction $extraction, User $user, array $validated = []): array
    {
        return DB::transaction(function () use ($extraction, $user, $validated): array {
            $oldValues = $this->reviewValues($extraction);
            $reason = (string) ($validated['note'] ?? $validated['reason'] ?? 'rejected_by_user');
            $output = $this->withReviewMetadata($extraction, 'rejected', $reason, $user);

            $extraction->forceFill([
                'output_json' => $output,
                'requires_human_review' => true,
                'review_reason' => $reason,
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
                'accepted_at' => null,
                'rejected_at' => now(),
            ])->save();

            $extraction->emailMessage?->forceFill(['status' => 'needs_review'])->save();

            $this->auditReview('ai_extraction_rejected', $extraction, $user, $oldValues, $this->reviewValues($extraction), $validated);

            return [
                'extraction' => $extraction,
                'status' => 'rejected',
                'message' => 'AI extraction rejected.',
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{extraction:AiEmailExtraction,status:string,message:string}
     */
    public function markNeedsReview(AiEmailExtraction $extraction, User $user, array $validated = []): array
    {
        return DB::transaction(function () use ($extraction, $user, $validated): array {
            $oldValues = $this->reviewValues($extraction);
            $reason = (string) ($validated['note'] ?? $validated['reason'] ?? 'human_review_requested');
            $output = $this->withReviewMetadata($extraction, 'needs_review', $reason, $user);

            $extraction->forceFill([
                'output_json' => $output,
                'requires_human_review' => true,
                'review_reason' => $reason,
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
            ])->save();

            $extraction->emailMessage?->forceFill(['status' => 'needs_review'])->save();

            $this->auditReview('ai_extraction_marked_needs_review', $extraction, $user, $oldValues, $this->reviewValues($extraction), $validated);

            return [
                'extraction' => $extraction,
                'status' => 'needs_review',
                'message' => 'AI extraction kept in human review.',
            ];
        });
    }

    /**
     * Backward-compatible method name.
     *
     * @return array{extraction:AiEmailExtraction,status:string,message:string}
     */
    public function requestHumanReview(AiEmailExtraction $extraction, User $user): array
    {
        return $this->markNeedsReview($extraction, $user);
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewValues(AiEmailExtraction $extraction): array
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

    private function withReviewMetadata(AiEmailExtraction $extraction, string $decision, ?string $note, User $user): array
    {
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $output['_human_review'] = [
            'decision' => $decision,
            'note' => $note,
            'reviewed_by_user_id' => $user->id,
            'reviewed_at' => now()->toISOString(),
        ];

        return $output;
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $validated
     */
    private function auditReview(string $eventType, AiEmailExtraction $extraction, User $user, array $oldValues, array $newValues, array $validated): void
    {
        $extraction->loadMissing('emailMessage:id,company_id');

        $this->auditLogService->write($eventType, $extraction, $user, $oldValues, $newValues, [
            'decision' => str($eventType)->after('ai_extraction_')->toString(),
            'note' => $validated['note'] ?? $validated['reason'] ?? null,
            'reviewed_by_user_id' => $user->id,
            'email_message_id' => $extraction->email_message_id,
            'extraction_id' => $extraction->id,
        ], $extraction->emailMessage?->company_id);
    }
}
