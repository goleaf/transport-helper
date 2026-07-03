<?php

namespace App\Console\Commands;

use App\Services\Supply\Integrations\IntegrationOnboardingChecklistService;
use Illuminate\Console\Command;

class OnboardingChecklistCommand extends Command
{
    protected $signature = 'supply:onboarding-checklist {--json : Output JSON}';

    protected $description = 'Show the real data onboarding readiness checklist.';

    public function handle(IntegrationOnboardingChecklistService $service): int
    {
        $result = $service->run();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Onboarding Checklist: '.strtoupper((string) $result['status']));
        $this->table(
            ['Key', 'Status', 'Message'],
            collect($result['items'])->map(fn (array $item): array => [
                $item['key'],
                strtoupper((string) $item['status']),
                $item['message'],
            ])->all(),
        );

        return self::SUCCESS;
    }
}
