<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Supply\Procurement\ProcurementReportService;
use Illuminate\Console\Command;

class BudgetStatusCommand extends Command
{
    protected $signature = 'supply:budget-status
                            {--company_id= : Company ID}
                            {--date= : Budget date}
                            {--json : Output JSON}';

    protected $description = 'Show procurement budget status.';

    public function handle(ProcurementReportService $reports): int
    {
        $companyId = $this->option('company_id') ?: Company::query()->select(['id'])->value('id');
        $filters = array_filter([
            'company_id' => $companyId ? (int) $companyId : null,
            'date' => $this->option('date'),
        ]);
        $report = $reports->budgetStatus($filters);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Procurement budget status');
        $this->table(array_keys($report['rows'][0] ?? ['message' => 'Message']), array_map('array_values', $report['rows'] ?: [['message' => 'No rows available']]));

        return self::SUCCESS;
    }
}
