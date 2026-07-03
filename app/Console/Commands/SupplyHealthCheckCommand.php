<?php

namespace App\Console\Commands;

use App\Enums\EmailDirection;
use App\Enums\ImportBatchStatus;
use App\Models\CalculationRun;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\FailedJob;
use App\Models\ImportBatch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('supply:health-check')]
#[Description('Validate Supply / Procurement Agent operational readiness')]
class SupplyHealthCheckCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $criticalFailures = 0;
        $warnings = 0;

        foreach ($this->checks() as $check) {
            $status = $check['status'];
            $label = $check['label'];
            $details = $check['details'] ?? null;

            if ($status === 'fail') {
                $criticalFailures++;
                $this->error("[FAIL] {$label}".($details ? " - {$details}" : ''));

                continue;
            }

            if ($status === 'warn') {
                $warnings++;
                $this->warn("[WARN] {$label}".($details ? " - {$details}" : ''));

                continue;
            }

            $this->info("[OK] {$label}".($details ? " - {$details}" : ''));
        }

        if ($criticalFailures > 0) {
            $this->error("Supply health check failed with {$criticalFailures} critical issue(s) and {$warnings} warning(s).");

            return self::FAILURE;
        }

        if ($warnings > 0) {
            $this->warn("Supply health check completed with {$warnings} warning(s).");

            return self::SUCCESS;
        }

        $this->info('Supply health check completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{label:string,status:string,details?:string}>
     */
    private function checks(): array
    {
        return [
            $this->databaseConnectionCheck(),
            $this->queueConfiguredCheck(),
            $this->storageWritableCheck(),
            $this->emailAccountsConfiguredCheck(),
            $this->failedJobsCountCheck(),
            $this->lastSuccessfulImportCheck(),
            $this->lastCalculationRunCheck(),
            $this->lastEmailIngestionCheck(),
            $this->lastBackupMarkerCheck(),
        ];
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function databaseConnectionCheck(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'label' => 'Database connection',
                'status' => 'ok',
                'details' => config('database.default'),
            ];
        } catch (Throwable $exception) {
            return [
                'label' => 'Database connection',
                'status' => 'fail',
                'details' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function queueConfiguredCheck(): array
    {
        $connection = (string) config('queue.default');

        if ($connection === '') {
            return [
                'label' => 'Queue configured',
                'status' => 'warn',
                'details' => 'queue.default is empty',
            ];
        }

        return [
            'label' => 'Queue configured',
            'status' => $connection === 'sync' ? 'warn' : 'ok',
            'details' => $connection === 'sync' ? 'sync driver is not suitable for background ingestion in production' : $connection,
        ];
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function storageWritableCheck(): array
    {
        $path = 'health/supply-health-check.txt';

        try {
            Storage::put($path, now()->toIso8601String());
            Storage::delete($path);

            return [
                'label' => 'Storage writable',
                'status' => 'ok',
            ];
        } catch (Throwable $exception) {
            return [
                'label' => 'Storage writable',
                'status' => 'fail',
                'details' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function emailAccountsConfiguredCheck(): array
    {
        $count = EmailAccount::query()
            ->where('is_active', true)
            ->count();

        return [
            'label' => 'Email accounts configured',
            'status' => $count > 0 ? 'ok' : 'warn',
            'details' => "{$count} active account(s)",
        ];
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function failedJobsCountCheck(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [
                'label' => 'Failed jobs count',
                'status' => 'warn',
                'details' => 'failed_jobs table is missing',
            ];
        }

        $count = FailedJob::query()->count();

        return [
            'label' => 'Failed jobs count',
            'status' => $count > 0 ? 'warn' : 'ok',
            'details' => (string) $count,
        ];
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function lastSuccessfulImportCheck(): array
    {
        $batch = ImportBatch::query()
            ->select(['id', 'status', 'finished_at'])
            ->whereIn('status', [
                ImportBatchStatus::Completed->value,
                ImportBatchStatus::CompletedWithErrors->value,
            ])
            ->latest('finished_at')
            ->latest('id')
            ->first();

        return [
            'label' => 'Last successful import',
            'status' => $batch instanceof ImportBatch ? 'ok' : 'warn',
            'details' => $batch instanceof ImportBatch ? "batch {$batch->id} at {$batch->finished_at?->toDateTimeString()}" : 'none found',
        ];
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function lastCalculationRunCheck(): array
    {
        $run = CalculationRun::query()
            ->select(['id', 'status', 'finished_at', 'created_at'])
            ->latest('finished_at')
            ->latest('id')
            ->first();

        return [
            'label' => 'Last calculation run',
            'status' => $run instanceof CalculationRun ? 'ok' : 'warn',
            'details' => $run instanceof CalculationRun ? "run {$run->id} status {$run->status}" : 'none found',
        ];
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function lastEmailIngestionCheck(): array
    {
        $email = EmailMessage::query()
            ->select(['id', 'received_at', 'created_at'])
            ->where('direction', EmailDirection::Inbound->value)
            ->latest('received_at')
            ->latest('id')
            ->first();

        return [
            'label' => 'Last email ingestion',
            'status' => $email instanceof EmailMessage ? 'ok' : 'warn',
            'details' => $email instanceof EmailMessage ? "email {$email->id} at {$email->received_at?->toDateTimeString()}" : 'none found',
        ];
    }

    /**
     * @return array{label:string,status:string,details?:string}
     */
    private function lastBackupMarkerCheck(): array
    {
        $path = 'backups/last-successful-backup.json';

        return [
            'label' => 'Last backup marker',
            'status' => Storage::exists($path) ? 'ok' : 'warn',
            'details' => Storage::exists($path) ? $path : 'not implemented or marker missing',
        ];
    }
}
