<?php

namespace App\Actions;

use App\Enums\AiSuggestionType;
use App\Models\ManufacturerEmail;
use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessManufacturerEmailAction
{
    public function __construct(
        public ExtractManufacturerEmailFieldsAction $extractManufacturerEmailFields,
        public CreateAiSuggestionAction $createAiSuggestion,
        public RecordSupplyAuditAction $recordSupplyAudit,
    ) {}

    public function handle(
        string $fromEmail,
        string $subject,
        string $body,
        Carbon $receivedAt,
        ?User $actor = null,
        ?string $messageId = null,
    ): ManufacturerEmail {
        $extracted = $this->extractManufacturerEmailFields->handle($subject, $body);

        return DB::transaction(function () use ($actor, $body, $extracted, $fromEmail, $messageId, $receivedAt, $subject): ManufacturerEmail {
            $order = $extracted['order_number'] === null
                ? null
                : SupplyOrder::query()
                    ->where('order_number', $extracted['order_number'])
                    ->first();

            $email = ManufacturerEmail::query()->create([
                'supply_order_id' => $order?->getKey(),
                'processed_by_id' => $actor?->getKey(),
                'message_id' => $messageId,
                'from_email' => $fromEmail,
                'subject' => $subject,
                'body' => $body,
                'extracted_order_number' => null,
                'extracted_confirmation_number' => null,
                'extracted_ready_on' => null,
                'extracted_pickup_on' => null,
                'received_at' => $receivedAt,
                'processed_at' => now(),
                'automation_source' => 'email_autofill',
            ]);

            if ($extracted['confirmation_number'] !== null) {
                $this->createAiSuggestion->handle(
                    type: AiSuggestionType::EmailConfirmation,
                    payload: [
                        'order_number' => $extracted['order_number'],
                        'confirmation_number' => $extracted['confirmation_number'],
                        'ready_on' => $extracted['ready_on'],
                        'pickup_on' => $extracted['pickup_on'],
                    ],
                    confidenceScore: $order === null ? 55 : 92,
                    order: $order,
                    email: $email,
                    actor: $actor,
                    sourceAdapter: 'incoming_email_ai',
                    conflicts: $order === null ? ['order_number' => 'No matching supply order was found.'] : [],
                );
            }

            $this->recordSupplyAudit->handle($actor, 'manufacturer.email_processed', $email, [
                'supply_order_id' => $order?->getKey(),
                'has_confirmation_suggestion' => $extracted['confirmation_number'] !== null,
            ]);

            return $email->refresh()->load(['supplyOrder', 'aiSuggestions']);
        });
    }
}
