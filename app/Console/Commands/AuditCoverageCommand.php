<?php

namespace App\Console\Commands;

use App\Services\Supply\Security\AuditCoverageService;
use Illuminate\Console\Command;

class AuditCoverageCommand extends Command
{
    protected $signature = 'supply:audit-coverage
                            {--json : Output JSON}
                            {--strict : Return non-zero when warnings exist}';

    protected $description = 'Audit Supply / Procurement Agent audit event coverage.';

    public function handle(AuditCoverageService $service): int
    {
        $result = $service->run();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($result);
        }

        $this->info('Audit Coverage: '.strtoupper((string) $result['status']));
        $this->table(['Name', 'Status', 'Message'], collect($result['checks'])->map(fn (array $check): array => [
            $check['name'],
            strtoupper((string) $check['status']),
            $check['message'],
        ])->all());

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
