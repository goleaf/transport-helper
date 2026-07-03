<?php

namespace App\Console\Commands;

use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotReportService;
use Illuminate\Console\Command;

class PilotUatReportCommand extends Command
{
    protected $signature = 'supply:pilot-uat-report {pilot_id} {--json : Output JSON}';

    protected $description = 'Generate a pilot UAT report summary.';

    public function handle(PilotReportService $service): int
    {
        $pilot = PilotSupplier::query()->findOrFail((int) $this->argument('pilot_id'));
        $report = $service->generateUatReport($pilot);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Pilot UAT report for '.$report['supplier']);
        $this->table(
            ['Metric', 'Value'],
            [
                ['Status', $report['status']],
                ['Live ready', $report['uat_evaluation']['live_ready'] ? 'yes' : 'no'],
                ['Blocking critical items', $report['uat_evaluation']['pending_critical_count']],
            ],
        );

        return self::SUCCESS;
    }
}
