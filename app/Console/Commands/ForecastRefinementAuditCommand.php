<?php

namespace App\Console\Commands;

use App\Enums\CalculationScenarioStatus;
use App\Enums\ReplenishmentProfileStatus;
use App\Enums\TrendOverrideStatus;
use App\Models\CalculationScenario;
use App\Models\Company;
use App\Models\ReplenishmentProfile;
use App\Models\SalesExclusionRule;
use App\Models\TrendOverride;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ForecastRefinementAuditCommand extends Command
{
    protected $signature = 'supply:forecast-refinement-audit
                            {--json : Output JSON}
                            {--strict : Return non-zero when warnings exist}';

    protected $description = 'Audit deterministic forecast refinement setup and boundaries.';

    public function handle(AuditLogService $auditLogService): int
    {
        $checks = [
            $this->check('active_profiles', ReplenishmentProfile::query()->where('status', ReplenishmentProfileStatus::Active)->where('is_active', true)->count(), 'Active replenishment profiles.'),
            $this->check('approved_overrides', TrendOverride::query()->where('status', TrendOverrideStatus::Approved)->count(), 'Approved trend overrides.'),
            $this->check('pending_overrides', TrendOverride::query()->where('status', TrendOverrideStatus::PendingApproval)->count(), 'Pending trend overrides.'),
            $this->check('exclusion_rules', SalesExclusionRule::query()->where('is_active', true)->count(), 'Active sales exclusion rules.'),
            $this->check('scenario_failures', CalculationScenario::query()->where('status', CalculationScenarioStatus::Failed)->count(), 'Failed scenarios.'),
            $this->boundaryCheck(),
            $this->noDtoCheck(),
        ];

        $warnings = collect($checks)->filter(fn (array $check): bool => $check['status'] === 'warning')->values()->all();
        $status = $warnings === [] ? 'ok' : 'warning';
        $result = [
            'status' => $status,
            'checks' => $checks,
        ];

        $auditLogService->write(
            'forecast_refinement_audit_run',
            null,
            User::query()->select(['id', 'name', 'email', 'role'])->where('role', 'admin')->first(),
            null,
            $result,
            [],
            Company::query()->value('id'),
        );

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($status);
        }

        $this->info('Forecast refinement audit: '.strtoupper($status));
        $this->table(
            ['Name', 'Status', 'Value', 'Message'],
            collect($checks)->map(fn (array $check): array => [
                $check['name'],
                strtoupper($check['status']),
                $check['value'],
                $check['message'],
            ])->all(),
        );

        return $this->exitCode($status);
    }

    private function check(string $name, int $value, string $message): array
    {
        return [
            'name' => $name,
            'status' => $name === 'scenario_failures' && $value > 0 ? 'warning' : 'ok',
            'value' => $value,
            'message' => $message,
        ];
    }

    private function boundaryCheck(): array
    {
        $forbidden = [
            'OpenAI',
            'Http::',
            'Guzzle',
            'EmailSenderInterface',
            'CarrierSelectionService',
            'SupplierConfirmationApplicationService',
            'SupplierOrderSendService',
        ];
        $source = collect(glob(app_path('Services/Supply/Forecasting/*.php')) ?: [])
            ->map(fn (string $file): string => file_get_contents($file) ?: '')
            ->implode("\n");
        $found = collect($forbidden)->filter(fn (string $needle): bool => str_contains($source, $needle))->values()->all();

        return [
            'name' => 'forecasting_boundary',
            'status' => $found === [] ? 'ok' : 'warning',
            'value' => count($found),
            'message' => $found === [] ? 'No external, AI, email or carrier dependencies found.' : 'Forbidden dependencies found: '.implode(', ', $found),
        ];
    }

    private function noDtoCheck(): array
    {
        $appPath = app_path();
        $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath)))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile())
            ->map(fn (SplFileInfo $file): string => $file->getPathname());
        $matches = $files->filter(fn (string $path): bool => preg_match('/(?:DTO|Dto)\.php$/', $path) === 1)->values()->all();

        return [
            'name' => 'no_dto',
            'status' => is_dir(app_path('Data')) || $matches !== [] ? 'warning' : 'ok',
            'value' => count($matches),
            'message' => is_dir(app_path('Data')) ? 'app/Data exists.' : 'No DTO files found.',
        ];
    }

    private function exitCode(string $status): int
    {
        if ($this->option('strict') && $status !== 'ok') {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
