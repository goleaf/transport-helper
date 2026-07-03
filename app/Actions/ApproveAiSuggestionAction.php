<?php

namespace App\Actions;

use App\Enums\AiSuggestionStatus;
use App\Enums\HumanReviewStatus;
use App\Models\AiSuggestion;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ApproveAiSuggestionAction
{
    public function __construct(public RecordSupplyAuditAction $recordSupplyAudit) {}

    public function handle(AiSuggestion $suggestion, User $actor): AiSuggestion
    {
        if (! $actor->canManageSupplyWorkflow()) {
            throw new DomainException('Only supply managers may approve AI suggestions.');
        }

        if ($suggestion->status !== AiSuggestionStatus::PendingReview) {
            throw new DomainException('Only pending AI suggestions can be approved.');
        }

        return DB::transaction(function () use ($actor, $suggestion): AiSuggestion {
            $suggestion->forceFill([
                'status' => AiSuggestionStatus::Approved,
                'reviewed_by_id' => $actor->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $suggestion->humanReviews()->update([
                'status' => HumanReviewStatus::Approved,
                'reviewed_by_id' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);

            $auditable = $suggestion->supplyOrder ?? $suggestion->manufacturerEmail;

            if ($auditable !== null) {
                $this->recordSupplyAudit->handle($actor, 'ai.suggestion_approved', $auditable, [
                    'ai_suggestion_id' => $suggestion->getKey(),
                    'type' => $suggestion->type->value,
                ]);
            }

            return $suggestion->refresh()->load('humanReviews');
        });
    }
}
