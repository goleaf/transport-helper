<?php

namespace App\Console\Commands;

use App\Models\IntegrationConnection;
use App\Services\Supply\Integrations\IntegrationConnectionTestService;
use Illuminate\Console\Command;

class TestIntegrationCommand extends Command
{
    protected $signature = 'supply:test-integration {connection_id} {--dry-run : Force dry-run} {--real : Allow a real provider call if governance permits} {--json : Output JSON}';

    protected $description = 'Run a governed integration connection test.';

    public function handle(IntegrationConnectionTestService $service): int
    {
        $connection = IntegrationConnection::query()->findOrFail((int) $this->argument('connection_id'));
        $real = (bool) $this->option('real');
        $result = $service->test($connection, [
            'dry_run' => ! $real,
            'allow_real_call' => $real,
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($this->withoutModel($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Integration test status: '.strtoupper((string) $result['status']));
        $this->line((string) $result['message']);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function withoutModel(array $result): array
    {
        unset($result['connection']);

        return $result;
    }
}
