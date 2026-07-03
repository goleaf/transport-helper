<?php

namespace App\Services\Supply\Backup;

class BackupVerificationService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function verify(array $options = []): array
    {
        if ((bool) ($options['ensure_directories'] ?? false)) {
            foreach ($this->storageDirectories() as $directory) {
                $path = storage_path('app/'.$directory);

                if (! is_dir($path)) {
                    mkdir($path, 0775, true);
                }
            }
        }

        $checks = [
            $this->markerExistsCheck(),
            $this->markerFreshnessCheck(),
            $this->envExampleCheck(),
            $this->backupPlanDocCheck(),
            $this->restoreInstructionsCheck(),
        ];

        foreach ($this->storageDirectories() as $directory) {
            $checks[] = $this->storageDirectoryCheck($directory);
        }

        return [
            'status' => $this->statusFromChecks($checks),
            'checks' => $checks,
        ];
    }

    /**
     * @return list<string>
     */
    private function storageDirectories(): array
    {
        return [
            'imports',
            'exports',
            'email-attachments',
            'form-autofill-outputs',
            'manufacturer-form-templates',
            'pilot',
            'backups',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function markerExistsCheck(): array
    {
        $path = $this->markerPath();
        $exists = $path !== '' && file_exists($path);

        return $this->check('backup_marker', $exists ? 'ok' : 'warning', $exists ? 'Backup marker exists.' : 'Backup marker is missing.', [
            'path_configured' => $path !== '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function markerFreshnessCheck(): array
    {
        $path = $this->markerPath();

        if ($path === '' || ! file_exists($path)) {
            return $this->check('backup_marker_freshness', 'warning', 'Backup marker freshness could not be checked.');
        }

        $maxAgeHours = (int) config('supply.backup.max_age_hours', 48);
        $ageHours = (time() - filemtime($path)) / 3600;
        $fresh = $ageHours <= $maxAgeHours;

        return $this->check('backup_marker_freshness', $fresh ? 'ok' : 'warning', $fresh ? 'Backup marker is fresh.' : 'Backup marker is older than configured max age.', [
            'max_age_hours' => $maxAgeHours,
            'age_hours' => round($ageHours, 2),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function storageDirectoryCheck(string $directory): array
    {
        $path = storage_path('app/'.$directory);
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);
        $status = $exists && $writable ? 'ok' : 'warning';

        return $this->check('storage_'.str_replace(['-', '/'], '_', $directory), $status, $status === 'ok' ? "Storage directory {$directory} exists and is writable." : "Storage directory {$directory} is missing or not writable.", [
            'path' => $path,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function envExampleCheck(): array
    {
        $source = is_file(base_path('.env.example')) ? (file_get_contents(base_path('.env.example')) ?: '') : '';
        $hasKeys = collect(['SUPPLY_BACKUP_MARKER_PATH', 'SUPPLY_BACKUP_MAX_AGE_HOURS', 'SUPPLY_LOCAL_MODE'])
            ->every(fn (string $key): bool => str_contains($source, $key));

        return $this->check('env_example_backup_keys', $hasKeys ? 'ok' : 'warning', $hasKeys ? '.env.example contains backup/readiness keys.' : '.env.example is missing backup/readiness keys.');
    }

    /**
     * @return array<string, mixed>
     */
    private function backupPlanDocCheck(): array
    {
        return $this->check('backup_plan_doc', is_file(base_path('docs/backup-plan.md')) ? 'ok' : 'warning', is_file(base_path('docs/backup-plan.md')) ? 'Backup plan document exists.' : 'Backup plan document is missing.');
    }

    /**
     * @return array<string, mixed>
     */
    private function restoreInstructionsCheck(): array
    {
        $paths = [
            base_path('docs/backup-plan.md'),
            base_path('docs/deployment/backup-and-restore.md'),
        ];
        $hasRestoreInstructions = collect($paths)
            ->filter(fn (string $path): bool => is_file($path))
            ->contains(fn (string $path): bool => str_contains(strtolower(file_get_contents($path) ?: ''), 'restore'));

        return $this->check('restore_instructions', $hasRestoreInstructions ? 'ok' : 'warning', $hasRestoreInstructions ? 'Restore instructions exist.' : 'Restore instructions are missing.');
    }

    private function markerPath(): string
    {
        return (string) config('supply.backup.marker_path', config('supply.health.backup_marker_path', ''));
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
