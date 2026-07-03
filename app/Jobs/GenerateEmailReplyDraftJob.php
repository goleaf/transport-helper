<?php

namespace App\Jobs;

use App\Contracts\AI\AiEmailReplyDraftGeneratorInterface;
use App\Enums\EmailDirection;
use App\Models\EmailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateEmailReplyDraftJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $emailMessageId,
    ) {}

    public function handle(AiEmailReplyDraftGeneratorInterface $generator): void
    {
        $emailMessage = EmailMessage::query()->findOrFail($this->emailMessageId);
        $draft = $generator->generate([
            'email_message_id' => $emailMessage->id,
            'from_email' => $emailMessage->from_email,
            'subject' => $emailMessage->subject,
            'body_text' => $emailMessage->body_text,
        ]);

        EmailMessage::query()->create([
            'company_id' => $emailMessage->company_id,
            'email_account_id' => $emailMessage->email_account_id,
            'direction' => EmailDirection::Outbound,
            'thread_id' => $emailMessage->thread_id,
            'from_email' => null,
            'to_json' => [$emailMessage->from_email],
            'cc_json' => [],
            'subject' => $draft['subject'] ?? 'Draft reply',
            'body_text' => $draft['body_text'] ?? '',
            'body_html' => $draft['body_html'] ?? null,
            'related_supplier_id' => $emailMessage->related_supplier_id,
            'related_supplier_order_id' => $emailMessage->related_supplier_order_id,
            'status' => 'draft',
            'raw_headers_json' => [
                'source' => 'ai_reply_draft',
                'requires_human_review' => true,
            ],
        ]);
    }
}
