<?php

namespace App\Console\Commands;

use App\Services\Supply\Logistics\LogisticsDelayMonitoringService;
use Illuminate\Console\Command;

class MonitorLogisticsCommand extends Command
{
    protected $signature = 'supply:monitor-logistics
                            {--company_id= : Company id}
                            {--dry-run : Do not update records or create notifications}
                            {--no-status-update : Do not update statuses}
                            {--expected-soon-days= : Days ahead for expected-soon detection}
                            {--json : Output JSON}';

    protected $description = 'Monitor logistics delays, missing data and expected arrivals.';

    public function handle(LogisticsDelayMonitoringService $service): int
    {
        $result = $service->monitor([
            'company_id' => $this->option('company_id') ? (int) $this->option('company_id') : null,
            'dry_run' => (bool) $this->option('dry-run'),
            'update_status' => ! (bool) $this->option('no-status-update'),
            'expected_soon_days' => $this->option('expected-soon-days') ? (int) $this->option('expected-soon-days') : null,
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(
            ['Checked', 'Delayed', 'Expected soon', 'Missing data', 'Notifications', 'Dry run'],
            [[
                $result['checked_count'],
                $result['delayed_count'],
                $result['expected_soon_count'],
                $result['missing_data_count'],
                $result['notifications_created'],
                $result['dry_run'] ? 'yes' : 'no',
            ]],
        );

        return self::SUCCESS;
    }
}
