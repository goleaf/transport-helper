<?php

namespace App\Jobs;

use App\Models\Company;
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
        public int $companyId,
        public ?int $emailAccountId = null,
        public string $providerName = 'manual',
        public array $options = [],
    ) {}

    public function handle(EmailIngestionService $emailIngestionService): void
    {
        $company = Company::query()->findOrFail($this->companyId);
        $account = $this->emailAccountId === null ? null : EmailAccount::query()->findOrFail($this->emailAccountId);

        $emailIngestionService->ingest($company, $account, $this->providerName, $this->options);
    }
}
