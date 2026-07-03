<?php

namespace App\Jobs;

use App\Actions\IngestIncomingEmailsAction;
use App\Adapters\ArrayIncomingEmailAdapter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IngestIncomingEmailsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  array<int, array<string, mixed>>  $emails
     */
    public function __construct(public array $emails) {}

    /**
     * Execute the job.
     */
    public function handle(IngestIncomingEmailsAction $ingestIncomingEmails): void
    {
        $ingestIncomingEmails->handle(new ArrayIncomingEmailAdapter($this->emails));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            report($exception);
        }
    }
}
