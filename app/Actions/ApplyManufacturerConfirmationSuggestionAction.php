<?php

namespace App\Actions;

use App\Enums\AiSuggestionStatus;
use App\Enums\AiSuggestionType;
use App\Enums\SupplyOrderStatus;
use App\Models\AiSuggestion;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApplyManufacturerConfirmationSuggestionAction
{
    public function __construct(public RecordSupplyAuditAction $recordSupplyAudit) {}

    public function handle(AiSuggestion $suggestion, User $actor): AiSuggestion
    {
        if ($suggestion->type !== AiSuggestionType::EmailConfirmation) {
            throw new DomainException('Only email confirmation suggestions can be applied as manufacturer confirmations.');
        }

        if ($suggestion->status !== AiSuggestionStatus::Approved) {
            throw new DomainException('AI confirmation suggestions must be approved before applying.');
        }

        $order = $suggestion->supplyOrder;

        if ($order === null) {
            throw new DomainException('The AI confirmation suggestion is not linked to a supply order.');
        }

        $payload = Validator::make($suggestion->payload, [
            'order_number' => ['required', 'string'],
            'confirmation_number' => ['required', 'string'],
            'ready_on' => ['nullable', 'date_format:Y-m-d'],
            'pickup_on' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();

        if ($payload['order_number'] !== $order->order_number) {
            throw new DomainException('The AI confirmation order number does not match the supply order.');
        }

        return DB::transaction(function () use ($actor, $order, $payload, $suggestion): AiSuggestion {
            $orderUpdates = [
                'status' => SupplyOrderStatus::Confirmed,
                'manufacturer_confirmation_number' => $payload['confirmation_number'],
            ];

            if (($payload['ready_on'] ?? null) !== null) {
                $orderUpdates['manufacturer_ready_on'] = $payload['ready_on'];
            }

            $order->forceFill($orderUpdates)->save();

            $suggestion->forceFill([
                'status' => AiSuggestionStatus::Applied,
                'applied_by_id' => $actor->getKey(),
                'applied_at' => now(),
            ])->save();

            $this->recordSupplyAudit->handle($actor, 'manufacturer.confirmation_applied', $order, [
                'ai_suggestion_id' => $suggestion->getKey(),
                'confirmation_number' => $payload['confirmation_number'],
                'ready_on' => $payload['ready_on'] ?? null,
                'pickup_on' => $payload['pickup_on'] ?? null,
            ]);

            return $suggestion->refresh();
        });
    }
}
