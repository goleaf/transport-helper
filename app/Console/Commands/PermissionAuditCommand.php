<?php

namespace App\Console\Commands;

use App\Services\Supply\Security\PermissionAuditService;
use Illuminate\Console\Command;

class PermissionAuditCommand extends Command
{
    protected $signature = 'supply:permissions-audit
                            {--json : Output JSON}
                            {--strict : Return non-zero when warnings exist}';

    protected $description = 'Audit Supply / Procurement Agent roles, permissions and policies.';

    public function handle(PermissionAuditService $service): int
    {
        $result = $service->run();

        return $this->renderResult('Permissions Audit', $result);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function renderResult(string $title, array $result): int
    {
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($result);
        }

        $this->info($title.': '.strtoupper((string) $result['status']));
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
