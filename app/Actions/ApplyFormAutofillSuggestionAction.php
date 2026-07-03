<?php

namespace App\Actions;

use App\Enums\AiSuggestionStatus;
use App\Enums\AiSuggestionType;
use App\Enums\ManufacturerFormSubmissionStatus;
use App\Enums\SupplyOrderStatus;
use App\Models\AiSuggestion;
use App\Models\ManufacturerFormSubmission;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApplyFormAutofillSuggestionAction
{
    public function __construct(public RecordSupplyAuditAction $recordSupplyAudit) {}

    public function handle(AiSuggestion $suggestion, User $actor): ManufacturerFormSubmission
    {
        if ($suggestion->type !== AiSuggestionType::FormAutofill) {
            throw new DomainException('Only form autofill suggestions can be applied as manufacturer form submissions.');
        }

        if ($suggestion->status !== AiSuggestionStatus::Approved) {
            throw new DomainException('AI form autofill suggestions must be approved before applying.');
        }

        $order = $suggestion->supplyOrder;

        if ($order === null) {
            throw new DomainException('The AI form autofill suggestion is not linked to a supply order.');
        }

        $payload = Validator::make($suggestion->payload, [
            'form_url' => ['nullable', 'string'],
            'fields' => ['required', 'array'],
        ])->validate();

        return DB::transaction(function () use ($actor, $order, $payload, $suggestion): ManufacturerFormSubmission {
            $submission = ManufacturerFormSubmission::query()->create([
                'supply_order_id' => $order->getKey(),
                'submitted_by_id' => $actor->getKey(),
                'status' => ManufacturerFormSubmissionStatus::Ready,
                'form_url' => $payload['form_url'] ?? null,
                'payload' => $payload['fields'],
                'automation_source' => 'approved_ai_form_autofill',
            ]);

            $order->forceFill([
                'status' => SupplyOrderStatus::FormReady,
            ])->save();

            $suggestion->forceFill([
                'status' => AiSuggestionStatus::Applied,
                'applied_by_id' => $actor->getKey(),
                'applied_at' => now(),
            ])->save();

            $this->recordSupplyAudit->handle($actor, 'manufacturer.form_autofill_applied', $order, [
                'ai_suggestion_id' => $suggestion->getKey(),
                'submission_id' => $submission->getKey(),
            ]);

            return $submission->refresh();
        });
    }
}
