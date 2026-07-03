<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Services\Email\EmailIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchEmailMessagesJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public int $emailAccountId,
        public array $options = [],
    ) {}

    public function handle(EmailIngestionService $emailIngestionService): void
    {
        $emailAccount = EmailAccount::query()->findOrFail($this->emailAccountId);

        $emailIngestionService->ingest($emailAccount, $this->options);
    }
}
