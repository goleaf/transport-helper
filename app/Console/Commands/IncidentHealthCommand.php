<?php

namespace App\Console\Commands;

use App\Services\Supply\Incidents\IncidentHealthIntegrationService;
use Illuminate\Console\Command;

class IncidentHealthCommand extends Command
{
    protected $signature = 'supply:incident-health
                            {--json : Output JSON}
                            {--strict : Fail when status is not ok}';

    protected $description = 'Show operational incident health summary.';

    public function handle(IncidentHealthIntegrationService $healthService): int
    {
        $summary = $healthService->healthSummary();

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->option('strict') && $summary['status'] !== 'ok' ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Incident health: '.strtoupper($summary['status']));
        $this->table(['Metric', 'Value'], collect($summary)->map(fn (mixed $value, string $key): array => [$key, $value])->values()->all());

        return $this->option('strict') && $summary['status'] !== 'ok' ? self::FAILURE : self::SUCCESS;
    }
}
