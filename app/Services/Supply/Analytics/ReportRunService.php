<?php

namespace App\Services\Supply\Analytics;

use App\Enums\ReportRunStatus;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReportRunService
{
    public function __construct(
        private readonly AnalyticsFilterService $filters,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function run(string $reportType, array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $run = ReportRun::query()->create([
            'company_id' => $normalized['company_id'],
            'report_type' => $reportType,
            'status' => ReportRunStatus::Running,
            'filters_json' => $normalized,
            'warnings_json' => [],
            'errors_json' => [],
            'started_by_user_id' => $user?->id,
            'started_at' => now(),
        ]);
        $this->audit->write('analytics_report_run_started', $run, $user, null, ['report_type' => $reportType]);

        try {
            $report = $this->resolve($reportType)->report($normalized, $user);
            $warnings = $report['warnings'] ?? [];
            $run->forceFill([
                'status' => $warnings === [] ? ReportRunStatus::Completed : ReportRunStatus::CompletedWithWarnings,
                'result_summary_json' => $report['summary'] ?? [],
                'warnings_json' => $warnings,
                'finished_at' => now(),
            ])->save();
            $this->audit->write('analytics_report_run_completed', $run, $user, null, [
                'report_type' => $reportType,
                'status' => $run->status->value,
                'warning_count' => count($warnings),
            ]);

            return ['report_run' => $run->fresh(), 'report' => $report];
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => ReportRunStatus::Failed,
                'errors_json' => ['message' => $exception->getMessage()],
                'finished_at' => now(),
            ])->save();
            $this->audit->write('analytics_report_run_failed', $run, $user, null, [
                'report_type' => $reportType,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function resolve(string $reportType): object
    {
        return match ($reportType) {
            'management_dashboard' => app(ManagementDashboardAnalyticsService::class),
            'supplier_performance' => app(SupplierPerformanceReportService::class),
            'forecast_accuracy' => app(ForecastAccuracyReportService::class),
            'stockout_risk' => app(StockoutRiskReportService::class),
            'order_proposal_quality' => app(OrderProposalQualityReportService::class),
            'supplier_confirmation_mismatches' => app(SupplierConfirmationMismatchReportService::class),
            'transport_performance' => app(TransportPerformanceReportService::class),
            'logistics_performance' => app(LogisticsPerformanceReportService::class),
            'receiving_accuracy' => app(ReceivingAccuracyReportService::class),
            'data_quality' => app(DataQualityReportService::class),
            'audit_kpis' => app(AuditKpiReportService::class),
            'operator_efficiency' => app(OperatorEfficiencyReportService::class),
            'import_quality' => app(ImportQualityReportService::class),
            'email_ai_review_quality' => app(EmailAiReviewQualityReportService::class),
            'form_autofill_quality' => app(FormAutofillQualityReportService::class),
            default => throw ValidationException::withMessages(['report_type' => 'Unsupported analytics report type.']),
        };
    }
}
