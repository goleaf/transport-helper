<?php

namespace App\Services\Supply\Analytics;

use App\Models\SavedReport;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class SavedReportService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @return list<SavedReport>
     */
    public function list(User $user, ?string $reportType = null): array
    {
        return SavedReport::query()
            ->select(['id', 'company_id', 'user_id', 'name', 'report_type', 'filters_json', 'columns_json', 'chart_config_json', 'is_shared', 'is_default', 'created_by_user_id', 'created_at'])
            ->when($reportType, fn ($query, string $type) => $query->where('report_type', $type))
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('created_by_user_id', $user->id)
                    ->orWhere('is_shared', true);
            })
            ->orderByDesc('is_default')
            ->latest('id')
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function create(array $validated, User $user): array
    {
        $report = SavedReport::query()->create([
            'company_id' => $validated['company_id'] ?? null,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'report_type' => $validated['report_type'],
            'filters_json' => $this->sanitize($validated['filters_json'] ?? []),
            'columns_json' => $validated['columns_json'] ?? null,
            'chart_config_json' => $validated['chart_config_json'] ?? null,
            'is_shared' => (bool) ($validated['is_shared'] ?? false),
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'created_by_user_id' => $user->id,
        ]);

        $this->audit->write('saved_report_created', $report, $user, null, [
            'report_type' => $report->report_type,
            'is_shared' => $report->is_shared,
        ]);

        return ['saved_report' => $report, 'report' => $report];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function update(SavedReport $report, array $validated, User $user): array
    {
        $this->ensureOwnerOrManager($report, $user);
        $oldValues = $report->only(['name', 'filters_json', 'is_shared', 'is_default']);
        $report->fill([
            'name' => $validated['name'] ?? $report->name,
            'report_type' => $validated['report_type'] ?? $report->report_type,
            'filters_json' => array_key_exists('filters_json', $validated) ? $this->sanitize($validated['filters_json'] ?? []) : $report->filters_json,
            'columns_json' => $validated['columns_json'] ?? $report->columns_json,
            'chart_config_json' => $validated['chart_config_json'] ?? $report->chart_config_json,
            'is_shared' => array_key_exists('is_shared', $validated) ? (bool) $validated['is_shared'] : $report->is_shared,
            'is_default' => array_key_exists('is_default', $validated) ? (bool) $validated['is_default'] : $report->is_default,
        ])->save();

        $this->audit->write('saved_report_updated', $report, $user, $oldValues, $report->only(['name', 'filters_json', 'is_shared', 'is_default']));

        return ['saved_report' => $report->fresh(), 'report' => $report->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(SavedReport $report, User $user): array
    {
        $this->ensureOwnerOrManager($report, $user);
        $this->audit->write('saved_report_deleted', $report, $user, $report->only(['name', 'report_type']), null);
        $report->delete();

        return ['deleted' => true];
    }

    /**
     * @return array<string, mixed>
     */
    public function setDefault(SavedReport $report, User $user): array
    {
        $this->ensureOwnerOrManager($report, $user);
        SavedReport::query()
            ->where('user_id', $report->user_id)
            ->where('report_type', $report->report_type)
            ->update(['is_default' => false]);
        $report->forceFill(['is_default' => true])->save();
        $this->audit->write('saved_report_default_set', $report, $user, null, ['report_type' => $report->report_type]);

        return ['saved_report' => $report->fresh(), 'report' => $report->fresh()];
    }

    private function ensureOwnerOrManager(SavedReport $report, User $user): void
    {
        if ($report->user_id === $user->id || $report->created_by_user_id === $user->id || $user->hasRole('admin') || $user->hasPermissionTo('manage_saved_reports')) {
            return;
        }

        throw ValidationException::withMessages(['saved_report' => 'You may only modify your own saved reports.']);
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function sanitize(array $value): array
    {
        $clean = [];
        foreach ($value as $key => $item) {
            if (str_contains((string) $key, 'secret') || str_contains((string) $key, 'password') || str_contains((string) $key, 'token') || str_contains((string) $key, 'api_key')) {
                continue;
            }

            $clean[$key] = is_array($item) ? $this->sanitize($item) : $item;
        }

        return $clean;
    }
}
