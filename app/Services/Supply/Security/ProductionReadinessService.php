<?php

namespace App\Services\Supply\Security;

use App\Services\Supply\Backup\BackupVerificationService;
use App\Services\Supply\Integrations\IntegrationAuditService;
use App\Services\Supply\Logistics\SupplyHealthCheckService;
use App\Services\Supply\Logistics\SupplySecurityCheckService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ProductionReadinessService
{
    public function __construct(
        private readonly SupplyHealthCheckService $healthCheckService,
        private readonly SupplySecurityCheckService $securityCheckService,
        private readonly PermissionAuditService $permissionAuditService,
        private readonly AuditCoverageService $auditCoverageService,
        private readonly BackupVerificationService $backupVerificationService,
        private readonly AiBoundaryAuditService $aiBoundaryAuditService,
        private readonly IntegrationAuditService $integrationAuditService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function run(array $options = []): array
    {
        $sections = [
            'health' => $this->healthCheckService->run($options),
            'security' => $this->securityCheckService->run(),
            'permissions' => $this->permissionAuditService->run(),
            'audit' => $this->auditCoverageService->run(),
            'backup' => $this->backupVerificationService->verify(),
            'ai_boundary' => $this->aiBoundaryAuditService->run(),
            'integrations' => $this->integrationAuditService->run(),
            'boundaries' => $this->boundarySummary(),
        ];
        $summary = $this->summary($sections);
        $status = $summary['error'] > 0 ? 'error' : ($summary['warning'] > 0 ? 'warning' : 'ok');

        return [
            'status' => $status,
            'sections' => $sections,
            'summary' => $summary,
            'strict_failed' => (bool) ($options['strict'] ?? false) && $status !== 'ok',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function boundarySummary(): array
    {
        $checks = [
            $this->check('no_app_data_directory', is_dir(app_path('Data')) ? 'error' : 'ok', is_dir(app_path('Data')) ? 'app/Data exists.' : 'No app/Data directory exists.'),
            $this->check('no_dto_classes', $this->dtoFiles() === [] ? 'ok' : 'error', $this->dtoFiles() === [] ? 'No DTO classes found.' : 'DTO files found.', ['files' => $this->dtoFiles()]),
            $this->check('local_mode_configured', config('supply.local_mode.enabled', true) ? 'ok' : 'warning', config('supply.local_mode.enabled', true) ? 'Local/private mode is enabled by default.' : 'Local/private mode is disabled.'),
        ];

        return [
            'status' => $this->statusFromChecks($checks),
            'checks' => $checks,
        ];
    }

    /**
     * @return list<string>
     */
    private function dtoFiles(): array
    {
        return collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile())
            ->filter(fn (SplFileInfo $file): bool => preg_match('/(?:DTO|Dto)\.php$/', $file->getFilename()) === 1)
            ->map(fn (SplFileInfo $file): string => str_replace(base_path().'/', '', $file->getPathname()))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @return array{ok:int, warning:int, error:int}
     */
    private function summary(array $sections): array
    {
        $statuses = collect($sections)
            ->map(fn (array $section): string => (string) ($section['status'] ?? 'warning'));

        return [
            'ok' => $statuses->filter(fn (string $status): bool => $status === 'ok')->count(),
            'warning' => $statuses->filter(fn (string $status): bool => $status === 'warning')->count(),
            'error' => $statuses->filter(fn (string $status): bool => $status === 'error')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function check(string $name, string $status, string $message, array $metadata = []): array
    {
        return compact('name', 'status', 'message', 'metadata');
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     */
    private function statusFromChecks(array $checks): string
    {
        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')) {
            return 'error';
        }

        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning')) {
            return 'warning';
        }

        return 'ok';
    }
}
