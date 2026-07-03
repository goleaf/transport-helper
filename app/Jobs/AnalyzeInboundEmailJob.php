<?php

namespace App\Jobs;

use App\Contracts\AI\AiEmailAnalyzerInterface;
use App\Enums\AiPromptVersion;
use App\Models\AiEmailExtraction;
use App\Models\EmailMessage;
use App\Services\AI\AiEmailExtractionValidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeInboundEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $emailMessageId,
    ) {}

    public function handle(AiEmailAnalyzerInterface $analyzer, AiEmailExtractionValidationService $validationService): void
    {
        $emailMessage = EmailMessage::query()
            ->with(['relatedSupplierOrder.items.product'])
            ->findOrFail($this->emailMessageId);
        $input = [
            'email_message_id' => $emailMessage->id,
            'message_id' => $emailMessage->message_id,
            'thread_id' => $emailMessage->thread_id,
            'from_email' => $emailMessage->from_email,
            'subject' => $emailMessage->subject,
            'body_text' => $emailMessage->body_text,
            'related_supplier_order_number' => $emailMessage->relatedSupplierOrder?->order_number,
        ];
        $output = $analyzer->analyze($input);
        $confidence = is_numeric($output['confidence'] ?? null) ? (float) $output['confidence'] : 0.0;

        $extraction = AiEmailExtraction::query()->create([
            'email_message_id' => $emailMessage->id,
            'provider' => 'configured_ai_provider',
            'model' => 'email-analyzer',
            'prompt_version' => AiPromptVersion::SupplierEmailParserV1,
            'input_hash' => hash('sha256', json_encode($input)),
            'output_json' => $output,
            'confidence' => $confidence,
            'requires_human_review' => true,
            'review_reason' => 'pending_human_approval',
        ]);

        $validation = $validationService->validate($extraction);

        if ($validation['status'] !== 'accepted') {
            $extraction->forceFill([
                'requires_human_review' => true,
                'review_reason' => implode(',', $validation['reasons']),
            ])->save();
        }
    }
}
