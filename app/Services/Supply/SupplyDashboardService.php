<?php

namespace App\Services\Supply;

use App\Enums\EmailDirection;
use App\Enums\FormAutofillRunStatus;
use App\Enums\LogisticsStatus;
use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\CalculationRun;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\SupplierOrder;

class SupplyDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [
            'replenishmentPriorities' => $this->replenishmentPriorities(),
            'latestCalculationRuns' => $this->latestCalculationRuns(),
            'proposalsNeedingReview' => $this->proposalsNeedingReview(),
            'supplierOrdersAwaitingAction' => $this->supplierOrdersAwaitingAction(),
            'emailsNeedingReview' => $this->emailsNeedingReview(),
            'formAutofillRunsNeedingReview' => $this->formAutofillRunsNeedingReview(),
            'logisticsDelays' => $this->logisticsDelays(),
        ];
    }

    private function replenishmentPriorities(): mixed
    {
        return OrderProposalItem::query()
            ->select(['id', 'order_proposal_id', 'product_id', 'status', 'recommended_quantity', 'requires_human_review', 'updated_at'])
            ->with([
                'product:id,sku,name',
                'orderProposal:id,supplier_id,status',
                'orderProposal.supplier:id,name',
            ])
            ->where(function ($query): void {
                $query
                    ->where('requires_human_review', true)
                    ->orWhereIn('status', [
                        OrderProposalItemStatus::NeedsReview->value,
                        OrderProposalItemStatus::Draft->value,
                    ]);
            })
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    private function latestCalculationRuns(): mixed
    {
        return CalculationRun::query()
            ->select(['id', 'supplier_id', 'calculation_date', 'formula_version', 'status', 'started_by_user_id', 'started_at', 'finished_at', 'created_at'])
            ->with([
                'supplier:id,name',
                'startedBy:id,name',
            ])
            ->latest('id')
            ->limit(8)
            ->get();
    }

    private function proposalsNeedingReview(): mixed
    {
        return OrderProposal::query()
            ->select(['id', 'supplier_id', 'calculation_run_id', 'status', 'total_lines', 'created_by_user_id', 'created_at', 'updated_at'])
            ->with([
                'supplier:id,name',
                'calculationRun:id,calculation_date',
                'createdBy:id,name',
            ])
            ->withCount([
                'items as lines_needing_review_count' => function ($query): void {
                    $query
                        ->where('requires_human_review', true)
                        ->orWhereIn('status', [
                            OrderProposalItemStatus::Draft->value,
                            OrderProposalItemStatus::NeedsReview->value,
                        ]);
                },
            ])
            ->whereIn('status', [
                OrderProposalStatus::Draft->value,
                OrderProposalStatus::NeedsReview->value,
            ])
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    private function supplierOrdersAwaitingAction(): mixed
    {
        return SupplierOrder::query()
            ->select(['id', 'supplier_id', 'order_number', 'status', 'order_date', 'updated_at'])
            ->with('supplier:id,name')
            ->whereIn('status', [
                SupplierOrderStatus::AwaitingApproval->value,
                SupplierOrderStatus::EmailPrepared->value,
                SupplierOrderStatus::Delayed->value,
                SupplierOrderStatus::NeedsReview->value,
            ])
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    private function emailsNeedingReview(): mixed
    {
        return EmailMessage::query()
            ->select(['id', 'direction', 'from_email', 'subject', 'received_at', 'related_supplier_id', 'related_supplier_order_id', 'status'])
            ->with([
                'relatedSupplier:id,name',
                'relatedSupplierOrder:id,order_number',
            ])
            ->where('direction', EmailDirection::Inbound->value)
            ->whereIn('status', ['received', 'needs_review'])
            ->latest('received_at')
            ->limit(10)
            ->get();
    }

    private function formAutofillRunsNeedingReview(): mixed
    {
        return FormAutofillRun::query()
            ->select(['id', 'email_message_id', 'form_template_id', 'status', 'confidence', 'created_by_user_id', 'created_at', 'updated_at'])
            ->with([
                'emailMessage:id,subject,from_email',
                'formTemplate:id,name,context_type',
                'createdBy:id,name',
            ])
            ->whereIn('status', [
                FormAutofillRunStatus::AiFilled->value,
                FormAutofillRunStatus::NeedsReview->value,
            ])
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    private function logisticsDelays(): mixed
    {
        return LogisticsRecord::query()
            ->select(['id', 'supplier_order_id', 'supplier_id', 'carrier_id', 'ready_date', 'pickup_date', 'delivery_date', 'status', 'updated_at'])
            ->with([
                'supplierOrder:id,order_number',
                'supplier:id,name',
                'carrier:id,name',
            ])
            ->whereIn('status', [
                LogisticsStatus::Delayed->value,
                LogisticsStatus::NeedsReview->value,
                LogisticsStatus::WaitingForReadyDate->value,
            ])
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }
}
