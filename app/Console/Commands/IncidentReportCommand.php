<?php

namespace App\Console\Commands;

use App\Services\Supply\Incidents\IncidentReportService;
use Illuminate\Console\Command;

class IncidentReportCommand extends Command
{
    protected $signature = 'supply:incident-report
                            {--company_id= : Company id}
                            {--date_from= : Start date}
                            {--date_to= : End date}
                            {--status= : Incident status}
                            {--severity= : Incident severity}
                            {--json : Output JSON}';

    protected $description = 'Show operational incident report.';

    public function handle(IncidentReportService $reportService): int
    {
        $filters = array_filter([
            'company_id' => $this->option('company_id') !== null ? (int) $this->option('company_id') : null,
            'date_from' => $this->option('date_from'),
            'date_to' => $this->option('date_to'),
            'status' => $this->option('status'),
            'severity' => $this->option('severity'),
        ], fn (mixed $value): bool => $value !== null && $value !== '');
        $report = $reportService->report($filters);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info($report['title']);
        $this->table(['Metric', 'Value'], collect($report['summary'])->map(fn (mixed $value, string $key): array => [
            $key,
            is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES) : $value,
        ])->values()->all());

        return self::SUCCESS;
    }
}
