<?php

namespace App\Console\Commands;

use App\Services\Supply\Incidents\IncidentAutoDetectionService;
use Illuminate\Console\Command;

class DetectOperationalIncidentsCommand extends Command
{
    protected $signature = 'supply:detect-incidents
                            {--company_id= : Company id}
                            {--dry-run : Do not create incidents}
                            {--type=* : Incident type filter}
                            {--max-per-type=50 : Maximum findings per type}
                            {--json : Output JSON}';

    protected $description = 'Detect blocked workflows and create operational incidents.';

    public function handle(IncidentAutoDetectionService $detectionService): int
    {
        $result = $detectionService->detect([
            'company_id' => $this->option('company_id') !== null ? (int) $this->option('company_id') : null,
            'dry_run' => (bool) $this->option('dry-run'),
            'types' => array_filter((array) $this->option('type')),
            'max_per_type' => (int) $this->option('max-per-type'),
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Incident detection '.($result['dry_run'] ? 'dry-run' : 'run').' completed.');
        $this->table(['Metric', 'Value'], [
            ['findings', $result['findings_count']],
            ['created', $result['incidents_created']],
            ['deduped', $result['deduped_count']],
        ]);

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }
}
