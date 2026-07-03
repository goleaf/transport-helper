<?php

namespace App\Console\Commands;

use App\Models\PilotSupplier;
use Illuminate\Console\Command;

class PilotOnboardingChecklistCommand extends Command
{
    protected $signature = 'supply:pilot-onboarding-checklist {--json : Output JSON}';

    protected $description = 'Show pilot supplier onboarding summary.';

    public function handle(): int
    {
        $pilots = PilotSupplier::query()
            ->select(['id', 'supplier_id', 'name', 'status', 'readiness_result_json'])
            ->with(['supplier:id,name'])
            ->withCount(['files', 'runs'])
            ->orderBy('id')
            ->get();

        $items = $pilots->map(fn (PilotSupplier $pilot): array => [
            'pilot_supplier_id' => $pilot->id,
            'supplier' => $pilot->supplier?->name,
            'name' => $pilot->name,
            'status' => $pilot->status,
            'files_count' => $pilot->files_count,
            'runs_count' => $pilot->runs_count,
            'readiness_status' => $pilot->readiness_result_json['status'] ?? 'not_run',
        ])->values()->all();

        $result = [
            'status' => collect($items)->contains(fn (array $item): bool => $item['readiness_status'] === 'failed') ? 'warning' : 'ok',
            'pilot_count' => count($items),
            'items' => $items,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Pilot onboarding checklist: '.strtoupper($result['status']));
        $this->table(
            ['ID', 'Supplier', 'Status', 'Files', 'Runs', 'Readiness'],
            collect($items)->map(fn (array $item): array => [
                $item['pilot_supplier_id'],
                $item['supplier'],
                $item['status'],
                $item['files_count'],
                $item['runs_count'],
                $item['readiness_status'],
            ])->all(),
        );

        return self::SUCCESS;
    }
}
