<?php

namespace App\Console\Commands;

use App\Services\Supply\Incidents\IncidentEscalationService;
use App\Services\Supply\Incidents\IncidentSlaService;
use Illuminate\Console\Command;

class MonitorIncidentSlaCommand extends Command
{
    protected $signature = 'supply:monitor-incident-sla
                            {--company_id= : Company id}
                            {--dry-run : Do not update SLA or create escalations}
                            {--json : Output JSON}';

    protected $description = 'Monitor operational incident SLA breaches and escalations.';

    public function handle(IncidentSlaService $slaService, IncidentEscalationService $escalationService): int
    {
        $options = [
            'company_id' => $this->option('company_id') !== null ? (int) $this->option('company_id') : null,
            'dry_run' => (bool) $this->option('dry-run'),
        ];
        $sla = $slaService->monitor($options);
        $escalations = $escalationService->monitorEscalations($options);
        $result = [
            'sla' => $sla,
            'escalations' => $escalations,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Incident SLA monitor completed.');
        $this->table(['Metric', 'Value'], [
            ['checked', $sla['checked_count']],
            ['breaches', $sla['breach_count']],
            ['escalations_created', $escalations['escalations_created']],
        ]);

        return self::SUCCESS;
    }
}
