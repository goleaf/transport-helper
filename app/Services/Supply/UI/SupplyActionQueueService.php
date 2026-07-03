<?php

namespace App\Services\Supply\UI;

use App\Enums\FormAutofillRunStatus;
use App\Enums\LogisticsStatus;
use App\Enums\OrderProposalItemStatus;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\LogisticsRecord;
use App\Models\OrderProposalItem;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class SupplyActionQueueService
{
    /**
     * @return list<array{priority:string,type:string,title:string,object_label:string,reason:string,url:string|null,age:string}>
     */
    public function items(?User $user = null, array $filters = []): array
    {
        return collect()
            ->merge($this->canSeeSupplyReview($user) ? $this->proposalReviewItems() : [])
            ->merge($this->canSeeEmailReview($user) ? $this->emailReviewItems() : [])
            ->merge($this->canSeeFormReview($user) ? $this->formAutofillItems() : [])
            ->merge($this->canSeeLogisticsReview($user) ? $this->logisticsDelayItems() : [])
            ->take(12)
            ->values()
            ->all();
    }

    private function canSeeSupplyReview(?User $user): bool
    {
        return $user === null
            || $user->canManageSupplyWorkflow()
            || $user->hasPermissionTo('approve_order_proposals')
            || $user->hasPermissionTo('adjust_order_quantities');
    }

    private function canSeeEmailReview(?User $user): bool
    {
        return $user === null
            || $user->canManageSupplyWorkflow()
            || $user->hasPermissionTo('approve_supplier_emails')
            || $user->hasPermissionTo('review_ai_extractions');
    }

    private function canSeeFormReview(?User $user): bool
    {
        return $user === null
            || $user->canManageSupplyWorkflow()
            || $user->hasPermissionTo('use_email_form_autofill')
            || $user->hasPermissionTo('apply_email_form_autofill');
    }

    private function canSeeLogisticsReview(?User $user): bool
    {
        return $user === null
            || $user->canManageLogisticsWorkflow()
            || $user->hasPermissionTo('view_logistics')
            || $user->hasPermissionTo('manage_logistics');
    }

    private function proposalReviewItems(): array
    {
        return OrderProposalItem::query()
            ->select(['id', 'order_proposal_id', 'product_id', 'status', 'requires_human_review', 'updated_at'])
            ->with(['product:id,sku,name'])
            ->where(function ($query): void {
                $query
                    ->where('requires_human_review', true)
                    ->orWhereIn('status', [
                        OrderProposalItemStatus::Draft->value,
                        OrderProposalItemStatus::NeedsReview->value,
                    ]);
            })
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->map(fn (OrderProposalItem $item): array => [
                'priority' => $item->requires_human_review ? 'high' : 'normal',
                'type' => 'proposal_review',
                'title' => 'Proposal line needs review',
                'object_label' => (string) ($item->product?->sku ?? 'Proposal item '.$item->getKey()),
                'reason' => 'Quantity approval, adjustment or rejection is required.',
                'url' => Route::has('supply.proposals.items.show') ? route('supply.proposals.items.show', [$item->order_proposal_id, $item]) : null,
                'age' => $item->updated_at?->diffForHumans() ?? '',
            ])
            ->all();
    }

    private function emailReviewItems(): array
    {
        return EmailMessage::query()
            ->select(['id', 'subject', 'status', 'received_at', 'updated_at'])
            ->whereIn('status', ['received', 'needs_review', 'analysis_pending'])
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->map(fn (EmailMessage $email): array => [
                'priority' => 'normal',
                'type' => 'email_review',
                'title' => 'Inbound email needs review',
                'object_label' => (string) ($email->subject ?? 'Email '.$email->getKey()),
                'reason' => 'Review supplier context, AI extraction or form autofill options.',
                'url' => Route::has('supply.emails.show') ? route('supply.emails.show', $email) : null,
                'age' => $email->received_at?->diffForHumans() ?? $email->updated_at?->diffForHumans() ?? '',
            ])
            ->all();
    }

    private function formAutofillItems(): array
    {
        return FormAutofillRun::query()
            ->select(['id', 'status', 'form_template_id', 'email_message_id', 'updated_at'])
            ->with(['formTemplate:id,name'])
            ->whereIn('status', [
                FormAutofillRunStatus::AiFilled->value,
                FormAutofillRunStatus::NeedsReview->value,
            ])
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->map(fn (FormAutofillRun $run): array => [
                'priority' => 'normal',
                'type' => 'form_autofill_review',
                'title' => 'Form autofill needs review',
                'object_label' => (string) ($run->formTemplate?->name ?? 'Autofill run '.$run->getKey()),
                'reason' => 'Extracted, normalized and final values need operator review.',
                'url' => Route::has('supply.form-autofill-runs.show') ? route('supply.form-autofill-runs.show', $run) : null,
                'age' => $run->updated_at?->diffForHumans() ?? '',
            ])
            ->all();
    }

    private function logisticsDelayItems(): array
    {
        return LogisticsRecord::query()
            ->select(['id', 'supplier_order_id', 'supplier_id', 'delivery_date', 'status', 'updated_at'])
            ->with(['supplierOrder:id,order_number'])
            ->whereIn('status', [
                LogisticsStatus::Delayed->value,
                LogisticsStatus::NeedsReview->value,
                LogisticsStatus::WaitingForReadyDate->value,
            ])
            ->latest('updated_at')
            ->limit(4)
            ->get()
            ->map(fn (LogisticsRecord $record): array => [
                'priority' => $record->status === LogisticsStatus::Delayed ? 'critical' : 'high',
                'type' => 'logistics_delay',
                'title' => 'Logistics follow-up required',
                'object_label' => (string) ($record->supplierOrder?->order_number ?? 'Logistics '.$record->getKey()),
                'reason' => 'Delivery, ready date or receiving status requires review.',
                'url' => Route::has('supply.logistics.show') ? route('supply.logistics.show', $record) : null,
                'age' => $record->updated_at?->diffForHumans() ?? '',
            ])
            ->all();
    }
}
