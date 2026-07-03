<?php

namespace App\Services\Supply\UI;

use App\Enums\FormAutofillRunStatus;
use App\Enums\LogisticsStatus;
use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Models\AiEmailExtraction;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\SupplierConfirmation;
use App\Models\User;

class SupplyDashboardService
{
    public function __construct(
        private readonly SupplyActionQueueService $actionQueue,
        private readonly SupplyEnvironmentBadgeService $environmentBadges,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function dashboard(?User $user = null): array
    {
        $queue = $this->actionQueue->items($user);

        return [
            'kpis' => $this->kpis(),
            'actionQueue' => $queue,
            'environmentBadges' => $this->environmentBadges->badges(),
            'timelineItems' => $this->timelineItems(),
            'risksBySupplier' => [],
            'hasActionQueue' => $queue !== [],
            'hasTimelineItems' => false,
        ];
    }

    /**
     * @return list<array{title:string,value:int|string,subtitle:string,tone:string,url:string|null}>
     */
    private function kpis(): array
    {
        return [
            [
                'title' => 'Replenishment Priorities',
                'value' => $this->proposalItemReviewCount(),
                'subtitle' => 'Proposal lines waiting for review',
                'tone' => 'warning',
                'url' => route('supply.proposals.index'),
            ],
            [
                'title' => 'Order Proposals Needing Review',
                'value' => OrderProposal::query()
                    ->whereIn('status', [OrderProposalStatus::Draft->value, OrderProposalStatus::NeedsReview->value])
                    ->count(),
                'subtitle' => 'Proposal headers not fully resolved',
                'tone' => 'warning',
                'url' => route('supply.proposals.index'),
            ],
            [
                'title' => 'Inbound Emails Needing Review',
                'value' => EmailMessage::query()->whereIn('status', ['received', 'needs_review', 'analysis_pending'])->count(),
                'subtitle' => 'Supplier replies needing operator review',
                'tone' => 'info',
                'url' => route('supply.emails.index'),
            ],
            [
                'title' => 'AI Extractions Needing Review',
                'value' => AiEmailExtraction::query()
                    ->whereNull('reviewed_at')
                    ->whereNull('accepted_at')
                    ->whereNull('rejected_at')
                    ->count(),
                'subtitle' => 'AI suggestions not yet accepted or rejected',
                'tone' => 'ai',
                'url' => route('supply.ai-extractions.index'),
            ],
            [
                'title' => 'Form Autofill Runs Needing Review',
                'value' => FormAutofillRun::query()
                    ->whereIn('status', [FormAutofillRunStatus::AiFilled->value, FormAutofillRunStatus::NeedsReview->value])
                    ->count(),
                'subtitle' => 'Extracted values waiting for user validation',
                'tone' => 'ai',
                'url' => route('supply.form-autofill-runs.index'),
            ],
            [
                'title' => 'Supplier Confirmations Needing Review',
                'value' => SupplierConfirmation::query()
                    ->whereIn('status', [
                        SupplierConfirmationStatus::NeedsReview->value,
                        SupplierConfirmationStatus::QuantityMismatch->value,
                        SupplierConfirmationStatus::DateMismatch->value,
                    ])
                    ->count(),
                'subtitle' => 'Discrepancies and date changes',
                'tone' => 'warning',
                'url' => route('supply.supplier-confirmations.index'),
            ],
            [
                'title' => 'Delayed Logistics',
                'value' => LogisticsRecord::query()
                    ->whereIn('status', [LogisticsStatus::Delayed->value, LogisticsStatus::NeedsReview->value])
                    ->count(),
                'subtitle' => 'Deliveries or readiness dates requiring follow-up',
                'tone' => 'logistics',
                'url' => route('supply.logistics.index'),
            ],
        ];
    }

    private function proposalItemReviewCount(): int
    {
        return OrderProposalItem::query()
            ->where(function ($query): void {
                $query
                    ->where('requires_human_review', true)
                    ->orWhereIn('status', [
                        OrderProposalItemStatus::Draft->value,
                        OrderProposalItemStatus::NeedsReview->value,
                    ]);
            })
            ->count();
    }

    /**
     * @return list<array{label:string,value:string,tone:string}>
     */
    private function timelineItems(): array
    {
        return [];
    }
}
