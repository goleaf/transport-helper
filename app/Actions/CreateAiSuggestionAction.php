<?php

namespace App\Actions;

use App\Enums\AiSuggestionStatus;
use App\Enums\AiSuggestionType;
use App\Enums\HumanReviewStatus;
use App\Models\AiSuggestion;
use App\Models\HumanReview;
use App\Models\ManufacturerEmail;
use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAiSuggestionAction
{
    public function __construct(public RecordSupplyAuditAction $recordSupplyAudit) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $conflicts
     */
    public function handle(
        AiSuggestionType $type,
        array $payload,
        int $confidenceScore,
        ?SupplyOrder $order = null,
        ?ManufacturerEmail $email = null,
        ?User $actor = null,
        string $sourceAdapter = 'ai',
        array $conflicts = [],
        ?string $notes = null,
    ): AiSuggestion {
        return DB::transaction(function () use ($actor, $confidenceScore, $conflicts, $email, $notes, $order, $payload, $sourceAdapter, $type): AiSuggestion {
            $suggestion = AiSuggestion::query()->create([
                'supply_order_id' => $order?->getKey(),
                'manufacturer_email_id' => $email?->getKey(),
                'created_by_id' => $actor?->getKey(),
                'type' => $type,
                'status' => AiSuggestionStatus::PendingReview,
                'confidence_score' => $confidenceScore,
                'requires_review' => true,
                'source_adapter' => $sourceAdapter,
                'payload' => $payload,
                'conflicts' => $conflicts,
                'notes' => $notes,
            ]);

            HumanReview::query()->create([
                'ai_suggestion_id' => $suggestion->getKey(),
                'status' => HumanReviewStatus::Pending,
                'reason' => $this->reviewReason($confidenceScore, $conflicts),
                'priority' => $confidenceScore < 70 || $conflicts !== [] ? 'high' : 'normal',
                'context' => [
                    'type' => $type->value,
                    'source_adapter' => $sourceAdapter,
                ],
            ]);

            $auditable = $order ?? $email;

            if ($auditable !== null) {
                $this->recordSupplyAudit->handle($actor, 'ai.suggestion_created', $auditable, [
                    'ai_suggestion_id' => $suggestion->getKey(),
                    'type' => $type->value,
                    'confidence_score' => $confidenceScore,
                    'requires_review' => true,
                ]);
            }

            return $suggestion->refresh()->load('humanReviews');
        });
    }

    /**
     * @param  array<string, mixed>  $conflicts
     */
    private function reviewReason(int $confidenceScore, array $conflicts): string
    {
        if ($conflicts !== []) {
            return 'ai_output_has_conflicts';
        }

        if ($confidenceScore < 70) {
            return 'ai_output_low_confidence';
        }

        return 'ai_output_requires_human_approval';
    }
}
