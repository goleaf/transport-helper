<?php

namespace App\Console\Commands;

use App\Services\Supply\Backup\BackupVerificationService;
use Illuminate\Console\Command;

class BackupVerificationCommand extends Command
{
    protected $signature = 'supply:backup-verify
                            {--json : Output JSON}
                            {--strict : Return non-zero when warnings exist}';

    protected $description = 'Verify Supply / Procurement Agent backup readiness.';

    public function handle(BackupVerificationService $service): int
    {
        $result = $service->verify(['ensure_directories' => true]);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($result);
        }

        $this->info('Backup Verification: '.strtoupper((string) $result['status']));
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
