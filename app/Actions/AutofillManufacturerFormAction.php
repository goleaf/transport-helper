<?php

namespace App\Actions;

use App\Enums\AiSuggestionType;
use App\Models\AiSuggestion;
use App\Models\SupplyOrder;
use App\Models\User;

class AutofillManufacturerFormAction
{
    public function __construct(public CreateAiSuggestionAction $createAiSuggestion) {}

    /**
     * @param  array<string, string>  $fieldMap
     */
    public function handle(SupplyOrder $order, array $fieldMap = [], ?User $actor = null): AiSuggestion
    {
        $order->loadMissing(['manufacturer', 'product']);

        $sourcePayload = [
            'purchase_order' => $order->order_number,
            'sku' => $order->product->sku,
            'product_name' => $order->product->name,
            'quantity' => $order->manufacturer_quantity,
            'unit' => $order->product->unit,
            'customer_reference' => $order->customer_reference,
            'requested_delivery_date' => $order->manufacturer_ready_on?->toDateString(),
        ];

        $payload = [];

        foreach ($sourcePayload as $sourceField => $value) {
            $targetField = $fieldMap[$sourceField] ?? $sourceField;
            $payload[$targetField] = $value;
        }

        return $this->createAiSuggestion->handle(
            type: AiSuggestionType::FormAutofill,
            payload: [
                'form_url' => $order->manufacturer->order_form_url,
                'fields' => $payload,
            ],
            confidenceScore: 88,
            order: $order,
            actor: $actor,
            sourceAdapter: 'email_form_autofill_ai',
        );
    }
}
