<?php

namespace App\Services\Supply\Analytics;

use App\Models\Company;
use App\Models\ExportFile;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnalyticsExportService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array<string, mixed>  $reportData
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportCsv(string $reportType, array $reportData, array $filters, ?User $user = null): array
    {
        $rows = $this->sanitizeRows($reportData['rows'] ?? []);
        $headers = collect($rows)->first() ? array_keys(collect($rows)->first()) : ['message'];
        $lines = [implode(',', $headers)];

        foreach ($rows ?: [['message' => 'No rows available']] as $row) {
            $lines[] = implode(',', array_map(fn (mixed $value): string => $this->csvValue($value), array_values($row)));
        }

        return $this->store($reportType, 'csv', implode("\n", $lines), 'text/csv', $filters, $user);
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportJson(string $reportType, array $reportData, array $filters, ?User $user = null): array
    {
        $payload = $this->sanitize([
            'generated_at' => now()->toISOString(),
            'report_type' => $reportType,
            'filters' => $filters,
            'summary' => $reportData['summary'] ?? [],
            'rows' => $reportData['rows'] ?? [],
            'warnings' => $reportData['warnings'] ?? [],
        ]);

        return $this->store($reportType, 'json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'application/json', $filters, $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function store(string $reportType, string $format, string $contents, string $mimeType, array $filters, ?User $user): array
    {
        $filename = $reportType.'-'.now()->format('Ymd-His').'-'.Str::random(6).'.'.$format;
        $path = 'exports/analytics/'.$reportType.'/'.$filename;
        Storage::disk('local')->put($path, $contents);

        $export = ExportFile::query()->create([
            'company_id' => $filters['company_id'] ?? Company::query()->select(['id'])->value('id'),
            'export_type' => 'analytics_'.$reportType.'_'.$format,
            'related_model_type' => null,
            'related_model_id' => null,
            'filename' => $filename,
            'stored_path' => $path,
            'mime_type' => $mimeType,
            'status' => 'stored',
            'created_by_user_id' => $user?->id,
        ]);

        $this->audit->logExport($export, 'analytics_report_exported', $user, [
            'report_type' => $reportType,
            'format' => $format,
        ]);

        return ['export_file' => $export, 'path' => $path, 'filename' => $filename];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sanitizeRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(fn (mixed $row): array => is_array($row) ? $this->sanitize($row) : ['value' => $row])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function sanitize(array $value): array
    {
        $clean = [];
        foreach ($value as $key => $item) {
            $keyString = (string) $key;
            if (str_contains($keyString, 'secret') || str_contains($keyString, 'password') || str_contains($keyString, 'token') || str_contains($keyString, 'encrypted_config') || $keyString === 'body_text' || $keyString === 'body_html') {
                continue;
            }

            $clean[$key] = is_array($item) ? $this->sanitize($item) : $item;
        }

        return $clean;
    }

    private function csvValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = json_encode($this->sanitize($value), JSON_UNESCAPED_SLASHES);
        }

        return '"'.str_replace('"', '""', (string) $value).'"';
    }
}
