<?php

namespace App\Jobs;

use App\Models\EmailMessage;
use App\Services\AI\Email\AiEmailAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeInboundEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public int $emailMessageId,
        public array $options = [],
    ) {}

    public function handle(AiEmailAnalysisService $analysisService): void
    {
        $email = EmailMessage::query()->findOrFail($this->emailMessageId);

        $analysisService->analyze($email, $this->options);
    }
}
