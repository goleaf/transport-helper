<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Supply\MasterData\MasterDataGovernanceAuditService;
use Illuminate\Console\Command;

class MasterDataGovernanceReportCommand extends Command
{
    protected $signature = 'supply:master-data-governance-report
                            {--company_id= : Company id}
                            {--json : Output JSON}';

    protected $description = 'Show master data governance status report.';

    public function handle(MasterDataGovernanceAuditService $service): int
    {
        $company = Company::query()
            ->when($this->option('company_id'), fn ($query) => $query->whereKey($this->option('company_id')))
            ->select(['id', 'name', 'code', 'timezone', 'default_currency'])
            ->orderBy('id')
            ->first();

        if (! $company instanceof Company) {
            $this->error('No company available for governance report.');

            return self::FAILURE;
        }

        $result = $service->audit($company);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Master data governance status: '.strtoupper($result['status']));
        $this->table(['Metric', 'Value'], collect($result['summary'])->map(fn (mixed $value, string $key): array => [$key, $value])->values()->all());

        return self::SUCCESS;
    }
}
