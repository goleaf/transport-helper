<?php

namespace App\Services\Supply\Pilot;

use App\Models\ExportFile;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Storage;

class PilotReportService
{
    public function __construct(
        private readonly PilotUatChecklistService $uatChecklistService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generateReadinessReport(PilotSupplier $pilot): array
    {
        $pilot->loadMissing(['supplier:id,name', 'company:id,name', 'files:id,pilot_supplier_id,file_type,original_filename,checksum', 'runs:id,pilot_supplier_id,run_type,status,finished_at']);

        return [
            'report_type' => 'readiness',
            'pilot_supplier_id' => $pilot->id,
            'supplier' => $pilot->supplier?->name,
            'company' => $pilot->company?->name,
            'status' => $pilot->status,
            'uploaded_files' => $pilot->files->map->only(['file_type', 'original_filename', 'checksum'])->values()->all(),
            'mappings' => [
                'import' => $pilot->import_mappings_json ?? [],
                'manufacturer_form' => $pilot->manufacturer_form_mapping_json ?? [],
                'email' => $pilot->email_sample_mapping_json ?? [],
                'carrier' => $pilot->carrier_mapping_json ?? [],
                'logistics' => $pilot->logistics_mapping_json ?? [],
            ],
            'readiness' => $pilot->readiness_result_json ?? [],
            'dry_runs' => $pilot->runs->map->only(['run_type', 'status', 'finished_at'])->values()->all(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function generateUatReport(PilotSupplier $pilot): array
    {
        return $this->generateReadinessReport($pilot) + [
            'report_type' => 'uat',
            'uat_checklist' => $this->uatChecklistService->getChecklist($pilot),
            'uat_evaluation' => $this->uatChecklistService->evaluate($pilot),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportReportCsv(PilotSupplier $pilot, string $reportType, User $user): array
    {
        $report = $reportType === 'uat'
            ? $this->generateUatReport($pilot)
            : $this->generateReadinessReport($pilot);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['section', 'key', 'value']);

        foreach ($this->flattenReport($report) as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $this->storeExport($pilot, $reportType, 'csv', 'text/csv', $content, $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportReportJson(PilotSupplier $pilot, string $reportType, User $user): array
    {
        $report = $reportType === 'uat'
            ? $this->generateUatReport($pilot)
            : $this->generateReadinessReport($pilot);

        return $this->storeExport($pilot, $reportType, 'json', 'application/json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}', $user);
    }

    /**
     * @return list<array{0:string,1:string,2:string}>
     */
    private function flattenReport(array $report, string $prefix = ''): array
    {
        $rows = [];

        foreach ($report as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                array_push($rows, ...$this->flattenReport($value, $path));

                continue;
            }

            $rows[] = [$prefix === '' ? 'root' : $prefix, (string) $key, is_scalar($value) ? (string) $value : ''];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function storeExport(PilotSupplier $pilot, string $reportType, string $extension, string $mimeType, string $content, User $user): array
    {
        $filename = 'pilot-'.$pilot->id.'-'.$reportType.'-'.now()->format('YmdHis').'.'.$extension;
        $path = 'exports/pilots/'.$pilot->id.'/'.$filename;
        Storage::disk('local')->put($path, $content);

        $export = ExportFile::query()->create([
            'company_id' => $pilot->company_id,
            'export_type' => 'pilot_'.$reportType.'_'.$extension,
            'related_model_type' => PilotSupplier::class,
            'related_model_id' => $pilot->id,
            'filename' => $filename,
            'stored_path' => $path,
            'mime_type' => $mimeType,
            'status' => 'stored',
            'created_by_user_id' => $user->id,
        ]);

        $this->auditLogService->write('pilot_report_exported', $export, $user, null, null, [
            'pilot_supplier_id' => $pilot->id,
            'report_type' => $reportType,
            'format' => $extension,
        ], $pilot->company_id);

        return [
            'export_file' => $export,
            'report' => $reportType === 'uat' ? $this->generateUatReport($pilot) : $this->generateReadinessReport($pilot),
        ];
    }
}
