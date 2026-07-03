<?php

namespace App\Actions;

use App\Enums\AiSuggestionType;
use App\Models\AiSuggestion;
use App\Models\SupplyOrder;
use App\Models\User;

class GenerateManufacturerReplyDraftAction
{
    public function __construct(public CreateAiSuggestionAction $createAiSuggestion) {}

    public function handle(SupplyOrder $order, string $prompt, ?User $actor = null): AiSuggestion
    {
        $order->loadMissing(['manufacturer', 'product']);

        return $this->createAiSuggestion->handle(
            type: AiSuggestionType::EmailReplyDraft,
            payload: [
                'draft_subject' => 'Re: Supply order '.$order->order_number,
                'draft_body' => $this->draftBody($order, $prompt),
                'prompt' => $prompt,
            ],
            confidenceScore: 80,
            order: $order,
            actor: $actor,
            sourceAdapter: 'email_reply_draft_ai',
        );
    }

    private function draftBody(SupplyOrder $order, string $prompt): string
    {
        return implode("\n\n", [
            'Hello '.$order->manufacturer->name.',',
            'Regarding supply order '.$order->order_number.' for '.$order->manufacturer_quantity.' '.$order->product->unit.' of '.$order->product->sku.'.',
            $prompt,
            'Please confirm the available pickup date and reference this order number in your reply.',
        ]);
    }
}
