<?php

namespace App\Console\Commands;

use App\Models\ReportSnapshot;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\Analytics\ReportRunService;
use Illuminate\Console\Command;

class AnalyticsSnapshotCommand extends Command
{
    protected $signature = 'supply:analytics-snapshot
                            {reportType? : Analytics report type}
                            {--date= : Snapshot date}';

    protected $description = 'Create a read-only analytics snapshot.';

    public function handle(ReportRunService $runs, AuditLogService $audit): int
    {
        $reportType = (string) ($this->argument('reportType') ?: 'management_dashboard');
        $result = $runs->run($reportType);
        $snapshot = ReportSnapshot::query()->create([
            'company_id' => $result['report']['filters']['company_id'] ?? null,
            'report_type' => $reportType,
            'snapshot_date' => $this->option('date') ?: now()->toDateString(),
            'metrics_json' => $result['report']['summary'] ?? [],
            'filters_json' => $result['report']['filters'] ?? [],
            'created_by_user_id' => null,
        ]);

        $audit->write('report_snapshot_created', $snapshot, null, null, [
            'report_type' => $reportType,
            'snapshot_date' => $snapshot->snapshot_date->toDateString(),
        ]);

        $this->info('Analytics snapshot created for '.$reportType.'.');

        return self::SUCCESS;
    }
}
