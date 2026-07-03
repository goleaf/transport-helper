<?php

namespace App\Services\Supply\Incidents;

use App\Models\Company;
use App\Models\ExportFile;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IncidentExportService
{
    public function __construct(
        private readonly IncidentReportService $reportService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportCsv(array $filters, User $user): array
    {
        $report = $this->reportService->report($filters);
        $rows = $report['rows'] ?? [];
        $headers = $rows === [] ? ['message'] : array_keys($rows[0]);
        $lines = [implode(',', $headers)];

        foreach ($rows ?: [['message' => 'No incidents']] as $row) {
            $lines[] = implode(',', array_map(fn (mixed $value): string => $this->csvValue($value), array_values($row)));
        }

        return $this->store('csv', implode("\n", $lines), 'text/csv', $filters, $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportJson(array $filters, User $user): array
    {
        $payload = [
            'generated_at' => now()->toISOString(),
            'filters' => $filters,
            'report' => $this->reportService->report($filters),
        ];

        return $this->store('json', json_encode($this->sanitize($payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'application/json', $filters, $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function store(string $format, string $contents, string $mimeType, array $filters, User $user): array
    {
        $filename = 'incident-report-'.now()->format('Ymd-His').'-'.Str::random(6).'.'.$format;
        $path = 'exports/incidents/'.$filename;
        Storage::disk('local')->put($path, $contents);

        $export = ExportFile::query()->create([
            'company_id' => $filters['company_id'] ?? Company::query()->select(['id'])->value('id'),
            'export_type' => 'incident_report_'.$format,
            'filename' => $filename,
            'stored_path' => $path,
            'mime_type' => $mimeType,
            'status' => 'stored',
            'created_by_user_id' => $user->id,
        ]);

        $this->auditLogService->logExport($export, 'incident_report_exported', $user, [
            'format' => $format,
        ]);

        return ['export_file' => $export, 'path' => $path, 'filename' => $filename];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitize(array $payload): array
    {
        return collect($payload)
            ->reject(fn (mixed $value, string|int $key): bool => str_contains((string) $key, 'secret') || str_contains((string) $key, 'password') || str_contains((string) $key, 'token') || str_contains((string) $key, 'encrypted_config'))
            ->map(fn (mixed $value): mixed => is_array($value) ? $this->sanitize($value) : $value)
            ->all();
    }

    private function csvValue(mixed $value): string
    {
        return '"'.str_replace('"', '""', (string) $value).'"';
    }
}
