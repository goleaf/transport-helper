<?php

namespace App\Console\Commands;

use App\Services\Supply\MasterData\UnknownSkuResolutionService;
use Illuminate\Console\Command;

class UnknownSkuReportCommand extends Command
{
    protected $signature = 'supply:unknown-sku-report
                            {--company_id= : Company id}
                            {--json : Output JSON}';

    protected $description = 'Show unresolved unknown SKU records.';

    public function handle(UnknownSkuResolutionService $service): int
    {
        $report = $service->unresolvedReport(['company_id' => $this->option('company_id')]);

        if ($this->option('json')) {
            $this->line(json_encode([
                'count' => $report['count'],
                'rows' => collect($report['rows'])->map(fn ($row): array => [
                    'id' => $row->id,
                    'unknown_sku' => $row->unknown_sku,
                    'source_type' => $row->source_type,
                    'status' => $row->status?->value,
                ])->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Unresolved unknown SKUs: '.$report['count']);
        $this->table(['ID', 'SKU', 'Supplier', 'Source'], collect($report['rows'])->map(fn ($row): array => [
            $row->id,
            $row->unknown_sku,
            $row->supplier?->name ?: 'No supplier',
            $row->source_type ?: 'Manual',
        ])->all());

        return self::SUCCESS;
    }
}
