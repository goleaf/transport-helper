<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\MasterData\MasterDataGovernanceAuditService;
use Illuminate\Console\Command;

class MasterDataQualityAuditCommand extends Command
{
    protected $signature = 'supply:master-data-quality-audit
                            {--company_id= : Company id}
                            {--json : Output JSON}
                            {--strict : Return non-zero when warnings exist}';

    protected $description = 'Audit master data quality, duplicates and unresolved SKU governance.';

    public function handle(MasterDataGovernanceAuditService $service, AuditLogService $auditLogService): int
    {
        $company = $this->company();

        if (! $company instanceof Company) {
            $this->error('No company available for master data audit.');

            return self::FAILURE;
        }

        $result = $service->audit($company);
        $auditLogService->write('master_data_quality_audit_run', null, User::query()->where('role', 'admin')->first(), null, $result, [], $company->getKey());

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($result['status']);
        }

        $this->info('Master data quality audit: '.strtoupper($result['status']));
        $this->table(['Metric', 'Value'], collect($result['summary'])->map(fn (mixed $value, string $key): array => [$key, $value])->values()->all());

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        return $this->exitCode($result['status']);
    }

    private function company(): ?Company
    {
        return Company::query()
            ->when($this->option('company_id'), fn ($query) => $query->whereKey($this->option('company_id')))
            ->select(['id', 'name', 'code', 'timezone', 'default_currency'])
            ->orderBy('id')
            ->first();
    }

    private function exitCode(string $status): int
    {
        return $this->option('strict') && $status !== 'ok' ? self::FAILURE : self::SUCCESS;
    }
}
