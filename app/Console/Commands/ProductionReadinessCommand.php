<?php

namespace App\Console\Commands;

use App\Services\Supply\Security\ProductionReadinessService;
use Illuminate\Console\Command;

class ProductionReadinessCommand extends Command
{
    protected $signature = 'supply:production-readiness
                            {--json : Output JSON}
                            {--strict : Return non-zero when warnings exist}';

    protected $description = 'Run aggregate Supply / Procurement Agent production readiness checks.';

    public function handle(ProductionReadinessService $service): int
    {
        $result = $service->run([
            'strict' => (bool) $this->option('strict'),
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($result);
        }

        $this->info('Production Readiness: '.strtoupper((string) $result['status']));
        $this->table(['Section', 'Status'], collect($result['sections'])->map(fn (array $section, string $name): array => [
            $name,
            strtoupper((string) ($section['status'] ?? 'warning')),
        ])->values()->all());

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
