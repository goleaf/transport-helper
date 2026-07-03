<?php

namespace App\Console\Commands;

use App\Services\Supply\Analytics\AnalyticsExportService;
use App\Services\Supply\Analytics\ReportRunService;
use Illuminate\Console\Command;

class AnalyticsExportCommand extends Command
{
    protected $signature = 'supply:analytics-export
                            {reportType : Analytics report type}
                            {--format=csv : csv or json}';

    protected $description = 'Export a Supply analytics report to private storage.';

    public function handle(ReportRunService $runs, AnalyticsExportService $exports): int
    {
        $reportType = (string) $this->argument('reportType');
        $result = $runs->run($reportType);
        $format = (string) $this->option('format');
        $export = $format === 'json'
            ? $exports->exportJson($reportType, $result['report'], $result['report']['filters'] ?? [])
            : $exports->exportCsv($reportType, $result['report'], $result['report']['filters'] ?? []);

        $this->info('Analytics export created: '.$export['path']);

        return self::SUCCESS;
    }
}
