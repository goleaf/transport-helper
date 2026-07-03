<?php

namespace App\Services\Supply\Forecasting;

use App\Models\CalculationScenario;
use App\Models\ExportFile;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Storage;

class ScenarioExportService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array{export: ExportFile, path: string}
     */
    public function exportCsv(CalculationScenario $scenario, User $user): array
    {
        $scenario->loadMissing(['company:id,name', 'supplier:id,name,code', 'items.product:id,sku,name,category']);
        $directory = 'exports/scenarios/'.$scenario->getKey();
        $filename = 'scenario-'.$scenario->getKey().'.csv';
        $path = $directory.'/'.$filename;
        $handle = fopen('php://temp', 'w+');

        fputcsv($handle, [
            'SKU',
            'Product',
            'Base Recommended',
            'Simulated Recommended',
            'Difference',
            'Trend Used',
            'Seasonality Factor',
            'Requires Review',
            'Warnings',
        ]);

        foreach ($scenario->items as $item) {
            fputcsv($handle, [
                $item->product?->sku,
                $item->product?->name,
                $item->base_recommended_quantity,
                $item->simulated_recommended_quantity,
                $item->difference_quantity,
                $item->trend_used,
                $item->seasonality_factor,
                $item->requires_human_review ? 'yes' : 'no',
                collect($item->warnings_json ?? [])->implode('; '),
            ]);
        }

        rewind($handle);
        Storage::put($path, stream_get_contents($handle) ?: '');
        fclose($handle);

        return $this->storedExport($scenario, $user, 'scenario_csv', $filename, $path, 'text/csv');
    }

    /**
     * @return array{export: ExportFile, path: string}
     */
    public function exportJson(CalculationScenario $scenario, User $user): array
    {
        $scenario->loadMissing(['company:id,name', 'supplier:id,name,code', 'items.product:id,sku,name,category']);
        $directory = 'exports/scenarios/'.$scenario->getKey();
        $filename = 'scenario-'.$scenario->getKey().'.json';
        $path = $directory.'/'.$filename;
        $payload = [
            'scenario' => [
                'id' => $scenario->getKey(),
                'name' => $scenario->name,
                'status' => $scenario->status?->value ?? $scenario->status,
                'formula_version' => $scenario->formula_version,
                'summary' => $scenario->summary_json,
                'warnings' => $scenario->warnings_json,
            ],
            'items' => $scenario->items->map(fn ($item): array => [
                'sku' => $item->product?->sku,
                'product_name' => $item->product?->name,
                'base_recommended_quantity' => $item->base_recommended_quantity,
                'simulated_recommended_quantity' => $item->simulated_recommended_quantity,
                'difference_quantity' => $item->difference_quantity,
                'trend_used' => $item->trend_used,
                'seasonality_factor' => $item->seasonality_factor,
                'requires_human_review' => $item->requires_human_review,
                'warnings' => $item->warnings_json,
            ])->values()->all(),
        ];

        Storage::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

        return $this->storedExport($scenario, $user, 'scenario_json', $filename, $path, 'application/json');
    }

    /**
     * @return array{export: ExportFile, path: string}
     */
    private function storedExport(CalculationScenario $scenario, User $user, string $type, string $filename, string $path, string $mime): array
    {
        $export = ExportFile::query()->create([
            'company_id' => $scenario->company_id,
            'export_type' => $type,
            'related_model_type' => CalculationScenario::class,
            'related_model_id' => $scenario->getKey(),
            'filename' => $filename,
            'stored_path' => $path,
            'mime_type' => $mime,
            'status' => 'stored',
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->auditLogService->logExport($export, 'scenario_exported', $user, [
            'scenario_id' => $scenario->getKey(),
            'format' => $type,
        ]);

        return ['export' => $export, 'path' => $path];
    }
}
