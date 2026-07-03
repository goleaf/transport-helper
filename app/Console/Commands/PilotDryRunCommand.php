<?php

namespace App\Console\Commands;

use App\Models\PilotSupplier;
use App\Services\Supply\Pilot\PilotDryRunService;
use Illuminate\Console\Command;

class PilotDryRunCommand extends Command
{
    protected $signature = 'supply:pilot-dry-run {pilot_id} {run_type} {--json : Output JSON}';

    protected $description = 'Run a safe pilot dry-run.';

    public function handle(PilotDryRunService $service): int
    {
        $pilot = PilotSupplier::query()->findOrFail((int) $this->argument('pilot_id'));
        $user = $pilot->createdBy()->first();

        if (! $user) {
            $this->error('Pilot has no created_by user for audit context.');

            return self::FAILURE;
        }

        $result = $service->runByType($pilot, (string) $this->argument('run_type'), $user);

        if ($this->option('json')) {
            $this->line(json_encode($result['result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Pilot dry-run: '.strtoupper((string) $result['result']['status']));
        $this->line((string) $result['result']['summary']);

        return self::SUCCESS;
    }
}
