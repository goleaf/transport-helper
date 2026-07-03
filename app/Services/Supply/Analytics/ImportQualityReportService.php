<?php

namespace App\Services\Supply\Analytics;

use App\Models\ImportBatch;
use App\Models\User;

class ImportQualityReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $batches = ImportBatch::query()
            ->select(['id', 'company_id', 'import_type', 'status', 'total_rows', 'successful_rows', 'failed_rows', 'started_at', 'finished_at'])
            ->with(['rows:id,import_batch_id,status,error_message'])
            ->latest('id')
            ->limit(500)
            ->get();
        $failedRows = $batches->flatMap->rows->where('status', 'failed');

        return [
            'type' => 'import_quality',
            'title' => 'Import Quality',
            'description' => 'Import batch and row error reporting.',
            'filters' => $normalized,
            'summary' => [
                'import_batches_count' => $batches->count(),
                'failed_rows_count' => (int) $batches->sum('failed_rows'),
                'error_rate' => $this->percentage((int) $batches->sum('failed_rows'), (int) $batches->sum('total_rows')),
                'dry_run_count' => $batches->filter(fn (ImportBatch $batch): bool => $this->status($batch->status) === 'dry_run')->count(),
                'real_import_count' => $batches->filter(fn (ImportBatch $batch): bool => $this->status($batch->status) !== 'dry_run')->count(),
            ],
            'top_error_messages' => $failedRows
                ->groupBy('error_message')
                ->map(fn ($group, string $message): array => ['message' => $message, 'count' => $group->count()])
                ->values()
                ->all(),
            'rows' => $batches->map(fn (ImportBatch $batch): array => [
                'import_batch_id' => $batch->id,
                'import_type' => $batch->import_type,
                'status' => $this->status($batch->status),
                'total_rows' => $batch->total_rows,
                'failed_rows' => $batch->failed_rows,
                'error_rate' => $this->percentage((int) $batch->failed_rows, (int) $batch->total_rows),
            ])->values()->all(),
            'warnings' => array_merge($normalized['warnings'], $batches->isEmpty() ? ['No import batches found.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }

    private function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
