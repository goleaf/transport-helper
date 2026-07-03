<?php

namespace App\Console\Commands;

use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotReadinessService;
use Illuminate\Console\Command;

class PilotReadinessCommand extends Command
{
    protected $signature = 'supply:pilot-readiness {pilot_id} {--json : Output JSON}';

    protected $description = 'Run readiness checks for a pilot supplier.';

    public function handle(PilotReadinessService $service): int
    {
        $pilot = PilotSupplier::query()->findOrFail((int) $this->argument('pilot_id'));
        $result = $service->check($pilot);

        if ($this->option('json')) {
            $this->line(json_encode($result['result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Pilot readiness: '.strtoupper((string) $result['result']['status']));
        $this->table(
            ['Type', 'Count'],
            [
                ['Warnings', count($result['result']['warnings'])],
                ['Errors', count($result['result']['errors'])],
            ],
        );

        return self::SUCCESS;
    }
}
