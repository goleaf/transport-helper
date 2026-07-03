<?php

namespace App\Services\Supply\Logistics;

use App\Enums\EmailDirection;
use App\Enums\HealthCheckStatus;
use App\Models\AiEmailExtraction;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\IntegrationConnection;
use App\Models\LogisticsRecord;
use App\Models\SupplierOrder;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SupplyHealthCheckService
{
    public function __construct(
        private readonly SupplySecurityCheckService $securityCheckService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function run(array $options = []): array
    {
        $checks = [
            $this->databaseCheck(),
            $this->storageCheck(),
            $this->simpleCheck('queue', filled(config('queue.default')), 'Queue connection configured.', 'Queue connection missing.'),
            $this->simpleCheck('app_key', filled(config('app.key')), 'Application key is set.', 'Application key is missing.', HealthCheckStatus::Error),
            $this->simpleCheck('migrations_table', Schema::hasTable('migrations'), 'Migrations table exists.', 'Migrations table missing.', HealthCheckStatus::Error),
            $this->countCheck('failed_jobs', Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0, 'failed job(s)', true),
            $this->countCheck('ai_extractions_needing_review', AiEmailExtraction::query()->where('requires_human_review', true)->count(), 'AI extraction(s) need review'),
            $this->countCheck('form_autofill_needing_review', FormAutofillRun::query()->where('status', 'needs_review')->count(), 'form autofill run(s) need review'),
            $this->countCheck('delayed_logistics_records', LogisticsRecord::query()->where('status', 'delayed')->count(), 'delayed logistics record(s)'),
            $this->countCheck('sent_supplier_orders_without_confirmation', SupplierOrder::query()->where('status', 'sent')->whereDoesntHave('confirmations')->count(), 'sent supplier order(s) without confirmation'),
            $this->countCheck('active_email_accounts_missing_config', EmailAccount::query()->where('is_active', true)->whereNotIn('provider', ['manual', 'log'])->whereNull('encrypted_config')->count(), 'active email account(s) missing config'),
            $this->countCheck('active_integrations_missing_config', IntegrationConnection::query()->where('is_active', true)->where('is_external', true)->whereNull('encrypted_config')->count(), 'active integration(s) missing config'),
            $this->latestEmailCheck(),
            $this->backupMarkerCheck(),
            $this->noDtoCheck(),
        ];

        foreach ($this->securityCheckService->run()['checks'] as $check) {
            $checks[] = $check;
        }

        $summary = [
            'ok' => collect($checks)->where('status', HealthCheckStatus::Ok->value)->count(),
            'warning' => collect($checks)->where('status', HealthCheckStatus::Warning->value)->count(),
            'error' => collect($checks)->where('status', HealthCheckStatus::Error->value)->count(),
        ];
        $status = $summary['error'] > 0 ? HealthCheckStatus::Error : ($summary['warning'] > 0 ? HealthCheckStatus::Warning : HealthCheckStatus::Ok);
        $result = [
            'status' => $status->value,
            'checks' => $checks,
            'summary' => $summary,
        ];

        $this->auditLogService->write('health_check_run', null, $options['user'] ?? null, null, null, [
            'status' => $result['status'],
            'warning_count' => $summary['warning'],
            'error_count' => $summary['error'],
            'strict' => (bool) ($options['strict'] ?? false),
        ]);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->check('database', HealthCheckStatus::Ok, 'Database connection OK.');
        } catch (Throwable $exception) {
            return $this->check('database', HealthCheckStatus::Error, 'Database connection failed.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function storageCheck(): array
    {
        try {
            Storage::disk('local')->put('health/supply-health-check.txt', now()->toISOString());
            Storage::disk('local')->delete('health/supply-health-check.txt');

            return $this->check('storage', HealthCheckStatus::Ok, 'Storage is writable.');
        } catch (Throwable $exception) {
            return $this->check('storage', HealthCheckStatus::Error, 'Storage is not writable.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function latestEmailCheck(): array
    {
        $email = EmailMessage::query()
            ->where('direction', EmailDirection::Inbound->value)
            ->latest('received_at')
            ->first();

        return $this->check('latest_email_ingestion', $email instanceof EmailMessage ? HealthCheckStatus::Ok : HealthCheckStatus::Warning, $email instanceof EmailMessage ? 'Inbound email exists.' : 'No inbound email found.');
    }

    /**
     * @return array<string, mixed>
     */
    private function backupMarkerCheck(): array
    {
        $path = (string) config('supply.health.backup_marker_path');
        $exists = $path !== '' && (str_starts_with($path, '/') ? file_exists($path) : Storage::disk('local')->exists($path));

        return $this->check('backup_marker', $exists ? HealthCheckStatus::Ok : HealthCheckStatus::Warning, $exists ? 'Backup marker found.' : 'Backup marker missing.', ['path_configured' => $path !== '']);
    }

    /**
     * @return array<string, mixed>
     */
    private function noDtoCheck(): array
    {
        $appPath = app_path();
        $hasDataDirectory = is_dir($appPath.'/Data');
        $dtoFiles = collect(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($appPath)))
            ->filter(fn (\SplFileInfo $file): bool => $file->isFile())
            ->contains(fn (\SplFileInfo $file): bool => preg_match('/(?:DTO|Dto)\.php$/', $file->getFilename()) === 1);

        return $this->check('no_dto_app_data', (! $hasDataDirectory && ! $dtoFiles) ? HealthCheckStatus::Ok : HealthCheckStatus::Error, 'No DTO/app/Data rule checked.');
    }

    /**
     * @return array<string, mixed>
     */
    private function simpleCheck(string $name, bool $condition, string $okMessage, string $warningMessage, HealthCheckStatus $failureStatus = HealthCheckStatus::Warning): array
    {
        return $this->check($name, $condition ? HealthCheckStatus::Ok : $failureStatus, $condition ? $okMessage : $warningMessage);
    }

    /**
     * @return array<string, mixed>
     */
    private function countCheck(string $name, int $count, string $label, bool $warningWhenPositive = false): array
    {
        $status = $count > 0 && ! $warningWhenPositive ? HealthCheckStatus::Warning : HealthCheckStatus::Ok;

        if ($warningWhenPositive && $count > 0) {
            $status = HealthCheckStatus::Warning;
        }

        return $this->check($name, $status, "{$count} {$label}.", ['count' => $count]);
    }

    /**
     * @return array<string, mixed>
     */
    private function check(string $name, HealthCheckStatus $status, string $message, array $metadata = []): array
    {
        return [
            'name' => $name,
            'status' => $status->value,
            'message' => $message,
            'metadata' => $metadata,
        ];
    }
}
