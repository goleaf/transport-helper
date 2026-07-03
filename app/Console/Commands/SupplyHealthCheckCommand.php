<?php

namespace App\Console\Commands;

use App\Services\Supply\Logistics\SupplyHealthCheckService;
use Illuminate\Console\Command;

class SupplyHealthCheckCommand extends Command
{
    protected $signature = 'supply:health-check
                            {--json : Output JSON}
                            {--strict : Return non-zero when warnings exist}';

    protected $description = 'Run Supply / Procurement Agent health checks.';

    public function handle(SupplyHealthCheckService $service): int
    {
        $result = $service->run([
            'strict' => (bool) $this->option('strict'),
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($result);
        }

        $this->info('Supply Health: '.strtoupper((string) $result['status']));
        foreach ($result['checks'] as $check) {
            $label = match ($check['status']) {
                'ok' => '[OK]',
                'warning' => '[WARN]',
                default => '[ERROR]',
            };
            $this->line($label.' '.$check['message']);
        }

        $this->table(
            ['Name', 'Status', 'Message'],
            collect($result['checks'])->map(fn (array $check): array => [
                $check['name'],
                strtoupper((string) $check['status']),
                $check['message'],
            ])->all(),
        );

        return $this->exitCode($result);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function exitCode(array $result): int
    {
        if ($result['status'] === 'error') {
            return self::FAILURE;
        }

        if ($this->option('strict') && $result['status'] === 'warning') {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
