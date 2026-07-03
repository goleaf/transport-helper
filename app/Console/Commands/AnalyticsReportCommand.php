<?php

namespace App\Console\Commands;

use App\Services\Supply\Analytics\ReportRunService;
use Illuminate\Console\Command;

class AnalyticsReportCommand extends Command
{
    protected $signature = 'supply:analytics-report
                            {reportType : Analytics report type}
                            {--date_from= : Start date}
                            {--date_to= : End date}
                            {--supplier_id= : Supplier id}
                            {--format=table : table or json}';

    protected $description = 'Run a read-only Supply analytics report.';

    public function handle(ReportRunService $runs): int
    {
        $result = $runs->run((string) $this->argument('reportType'), [
            'date_from' => $this->option('date_from'),
            'date_to' => $this->option('date_to'),
            'supplier_id' => $this->option('supplier_id'),
        ]);
        $report = $result['report'];

        if ($this->option('format') === 'json') {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info((string) $report['title']);
        $this->table(['Metric', 'Value'], collect($report['summary'] ?? [])->map(fn (mixed $value, string $key): array => [$key, is_array($value) ? json_encode($value) : $value])->values()->all());

        if (($report['warnings'] ?? []) !== []) {
            $this->warn('Warnings');
            foreach ($report['warnings'] as $warning) {
                $this->line('- '.$warning);
            }
        }

        return self::SUCCESS;
    }
}
