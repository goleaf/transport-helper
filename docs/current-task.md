# Current Task

## Task Title

Analytics And Management Reporting

## Task Goal

Create read-only analytics and management reporting for the Laravel Supply / Procurement Agent.

This task implements:
- management analytics dashboard;
- supplier performance report;
- forecast accuracy report;
- stockout risk report;
- order proposal quality report;
- supplier confirmation mismatch report;
- transport performance report;
- logistics performance report;
- receiving accuracy report;
- data quality report;
- audit KPI report;
- operator efficiency report;
- import quality report;
- email AI review quality report;
- form autofill quality report;
- saved reports;
- report runs;
- report exports;
- analytics commands;
- tests and documentation.

Analytics is read-only for business records.

## Required Reading

- AGENTS.md
- .codex/skills/00-global-rules.md
- .codex/skills/01-task-execution-loop.md
- .codex/skills/02-no-dto-rule.md
- .codex/skills/03-no-secrets-rule.md
- .codex/skills/04-testing-and-checks.md
- .codex/skills/05-git-commit-push.md
- .codex/skills/06-blockers-and-not-complete.md
- docs/architecture.md
- docs/domain-model.md
- docs/workflow-map.md
- docs/status-machines.md
- docs/decision-log.md
- docs/calculation-engine.md
- docs/import-export-adapters.md
- docs/order-proposal-workflow.md
- docs/supplier-order-email-workflow.md
- docs/inbound-email-ai-workflow.md
- docs/email-form-autofill.md
- docs/supplier-confirmation-workflow.md
- docs/transport-workflow.md
- docs/logistics-workflow.md
- docs/audit-and-security.md
- docs/production-readiness.md

## Non-Negotiable Rules

- Read this file from start to end.
- Create docs/current-task-read-confirmation.md before implementation.
- Create docs/current-task-progress.md before implementation.
- Do not create DTO.
- Do not create app/Data.
- Do not mutate business records from analytics.
- Do not call AI.
- Do not call OpenAI.
- Do not call external APIs.
- Do not call real email providers.
- Do not call carrier APIs.
- Do not call Google Sheets.
- Do not approve proposals.
- Do not send emails.
- Do not apply AI extraction.
- Do not apply form autofill.
- Do not apply supplier confirmation.
- Do not select carrier.
- Do not update logistics status.
- Do not record receiving.
- Do not change calculation formula.
- Do not expose secrets.
- Do not export full email bodies by default.
- Do not commit generated exports.
- Do not commit secrets.
- Do not claim success without checks.

## Scope

Create or update:

- database/migrations/* for saved_reports/report_runs/report_snapshots if missing
- app/Enums/ReportType.php
- app/Enums/ReportRunStatus.php
- app/Models/SavedReport.php
- app/Models/ReportRun.php
- app/Models/ReportSnapshot.php optional
- app/Services/Supply/Analytics/KpiDefinitionService.php
- app/Services/Supply/Analytics/AnalyticsFilterService.php
- app/Services/Supply/Analytics/ManagementDashboardAnalyticsService.php
- app/Services/Supply/Analytics/SupplierPerformanceReportService.php
- app/Services/Supply/Analytics/ForecastAccuracyReportService.php
- app/Services/Supply/Analytics/StockoutRiskReportService.php
- app/Services/Supply/Analytics/OrderProposalQualityReportService.php
- app/Services/Supply/Analytics/SupplierConfirmationMismatchReportService.php
- app/Services/Supply/Analytics/TransportPerformanceReportService.php
- app/Services/Supply/Analytics/LogisticsPerformanceReportService.php
- app/Services/Supply/Analytics/ReceivingAccuracyReportService.php
- app/Services/Supply/Analytics/DataQualityReportService.php
- app/Services/Supply/Analytics/AuditKpiReportService.php
- app/Services/Supply/Analytics/OperatorEfficiencyReportService.php
- app/Services/Supply/Analytics/ImportQualityReportService.php
- app/Services/Supply/Analytics/EmailAiReviewQualityReportService.php
- app/Services/Supply/Analytics/FormAutofillQualityReportService.php
- app/Services/Supply/Analytics/SavedReportService.php
- app/Services/Supply/Analytics/ReportRunService.php
- app/Services/Supply/Analytics/AnalyticsExportService.php
- app/Http/Requests/Supply/AnalyticsReportRequest.php
- app/Http/Requests/Supply/StoreSavedReportRequest.php
- app/Http/Requests/Supply/UpdateSavedReportRequest.php
- app/Http/Requests/Supply/ExportAnalyticsReportRequest.php
- app/Policies/SavedReportPolicy.php
- app/Policies/ReportRunPolicy.php
- app/Policies/AnalyticsPolicy.php optional
- app/Http/Controllers/Supply/AnalyticsDashboardController.php
- app/Http/Controllers/Supply/AnalyticsReportController.php
- app/Http/Controllers/Supply/AnalyticsExportController.php
- app/Http/Controllers/Supply/SavedReportController.php
- app/Http/Controllers/Supply/ReportRunController.php
- app/Console/Commands/AnalyticsReportCommand.php
- app/Console/Commands/AnalyticsExportCommand.php
- app/Console/Commands/AnalyticsSnapshotCommand.php optional
- routes/web.php
- routes/console.php or app/Console/Kernel.php
- resources/views/supply/analytics/dashboard.blade.php
- resources/views/supply/analytics/report.blade.php
- resources/views/supply/analytics/saved-reports/index.blade.php
- resources/views/supply/analytics/report-runs/index.blade.php
- resources/views/supply/analytics/report-runs/show.blade.php
- resources/views/supply/analytics/partials/report-filters.blade.php
- resources/views/supply/analytics/partials/kpi-card.blade.php
- resources/views/supply/analytics/partials/report-table.blade.php
- resources/views/supply/analytics/partials/warnings.blade.php
- resources/views/supply/analytics/partials/export-panel.blade.php
- resources/views/supply/analytics/partials/saved-report-panel.blade.php
- resources/views/supply/analytics/partials/simple-bar-chart.blade.php
- resources/views/supply/analytics/partials/risk-level-badge.blade.php
- config/supply.php update if needed
- tests/Unit/Analytics/KpiDefinitionServiceTest.php
- tests/Unit/Analytics/AnalyticsFilterServiceTest.php
- tests/Feature/Analytics/SupplierPerformanceReportServiceTest.php
- tests/Feature/Analytics/ForecastAccuracyReportServiceTest.php
- tests/Feature/Analytics/StockoutRiskReportServiceTest.php
- tests/Feature/Analytics/OrderProposalQualityReportServiceTest.php
- tests/Feature/Analytics/SupplierConfirmationMismatchReportServiceTest.php
- tests/Feature/Analytics/TransportPerformanceReportServiceTest.php
- tests/Feature/Analytics/LogisticsPerformanceReportServiceTest.php
- tests/Feature/Analytics/ReceivingAccuracyReportServiceTest.php
- tests/Feature/Analytics/DataQualityReportServiceTest.php
- tests/Feature/Analytics/AuditKpiReportServiceTest.php
- tests/Feature/Analytics/AnalyticsExportServiceTest.php
- tests/Feature/Analytics/SavedReportServiceTest.php
- tests/Feature/Analytics/AnalyticsControllerTest.php
- tests/Feature/Analytics/AnalyticsCommandTest.php
- tests/Unit/Analytics/AnalyticsBoundaryTest.php
- tests/Unit/NoDtoRuleTest.php update
- docs/analytics/overview.md
- docs/analytics/kpi-definitions.md
- docs/analytics/supplier-performance.md
- docs/analytics/forecast-accuracy.md
- docs/analytics/stockout-risk.md
- docs/analytics/transport-performance.md
- docs/analytics/logistics-performance.md
- docs/analytics/data-quality.md
- docs/analytics/audit-kpis.md
- docs/analytics/analytics-implementation-notes.md
- docs/workflow-map.md update
- docs/implementation-roadmap.md update
- docs/production-readiness.md update
- README.md update

## Out Of Scope

Do not implement:
- AI provider integration;
- real external API integration;
- BI tool integration;
- Google Sheets real sync;
- scheduled email reports;
- automatic business actions;
- new business modules;
- formula changes;
- UI/UX design system stage;
- operator command palette/saved table views stage.

## Required Implementation

Implement read-only analytics.

The reports must:
- support filters;
- show summary KPI cards or simple values;
- show warnings when data is incomplete;
- show formula/definition explanation;
- support CSV/JSON export;
- respect permissions;
- avoid exposing secrets;
- avoid exporting full email bodies;
- write audit logs for report runs and exports.

## Required Tests

Create or update:
- KpiDefinitionServiceTest
- AnalyticsFilterServiceTest
- SupplierPerformanceReportServiceTest
- ForecastAccuracyReportServiceTest
- StockoutRiskReportServiceTest
- OrderProposalQualityReportServiceTest
- SupplierConfirmationMismatchReportServiceTest
- TransportPerformanceReportServiceTest
- LogisticsPerformanceReportServiceTest
- ReceivingAccuracyReportServiceTest
- DataQualityReportServiceTest
- AuditKpiReportServiceTest
- AnalyticsExportServiceTest
- SavedReportServiceTest
- AnalyticsControllerTest
- AnalyticsCommandTest
- AnalyticsBoundaryTest
- NoDtoRuleTest

## Required Documentation

Create:
- docs/analytics/overview.md
- docs/analytics/kpi-definitions.md
- docs/analytics/supplier-performance.md
- docs/analytics/forecast-accuracy.md
- docs/analytics/stockout-risk.md
- docs/analytics/transport-performance.md
- docs/analytics/logistics-performance.md
- docs/analytics/data-quality.md
- docs/analytics/audit-kpis.md
- docs/analytics/analytics-implementation-notes.md

Update:
- docs/workflow-map.md
- docs/implementation-roadmap.md
- docs/production-readiness.md
- README.md

## Acceptance Criteria

- [ ] AGENTS.md read.
- [ ] docs/current-task.md created.
- [ ] docs/current-task.md read from start to end.
- [ ] docs/current-task-read-confirmation.md created.
- [ ] docs/current-task-progress.md created.
- [ ] Saved reports migration/model created if missing.
- [ ] Report runs migration/model created if missing.
- [ ] Report snapshots migration/model created or skipped with documented reason.
- [ ] ReportType enum/constants created.
- [ ] ReportRunStatus enum/constants created.
- [ ] KpiDefinitionService created.
- [ ] AnalyticsFilterService created.
- [ ] ManagementDashboardAnalyticsService created.
- [ ] SupplierPerformanceReportService created.
- [ ] ForecastAccuracyReportService created.
- [ ] StockoutRiskReportService created.
- [ ] OrderProposalQualityReportService created.
- [ ] SupplierConfirmationMismatchReportService created.
- [ ] TransportPerformanceReportService created.
- [ ] LogisticsPerformanceReportService created.
- [ ] ReceivingAccuracyReportService created.
- [ ] DataQualityReportService created.
- [ ] AuditKpiReportService created.
- [ ] OperatorEfficiencyReportService created.
- [ ] ImportQualityReportService created.
- [ ] EmailAiReviewQualityReportService created.
- [ ] FormAutofillQualityReportService created.
- [ ] SavedReportService created.
- [ ] ReportRunService created.
- [ ] AnalyticsExportService created.
- [ ] Permissions/policies created.
- [ ] FormRequests created.
- [ ] Controllers created.
- [ ] Routes created.
- [ ] Views created with existing/simple layout.
- [ ] Analytics commands created.
- [ ] Supplier performance report implemented.
- [ ] Forecast accuracy report implemented with insufficient data warning.
- [ ] Stockout risk report implemented.
- [ ] Order proposal quality report implemented.
- [ ] Supplier confirmation mismatch report implemented.
- [ ] Transport performance report implemented.
- [ ] Logistics performance report implemented.
- [ ] Receiving accuracy report implemented.
- [ ] Data quality report implemented.
- [ ] Audit KPI report implemented.
- [ ] Operator efficiency report implemented.
- [ ] Import quality report implemented.
- [ ] Email AI review quality report implemented.
- [ ] Form autofill quality report implemented.
- [ ] Saved reports implemented.
- [ ] Report runs implemented.
- [ ] CSV export implemented.
- [ ] JSON export implemented.
- [ ] Exports do not include secrets or full email bodies.
- [ ] Analytics audit events written.
- [ ] Analytics is read-only for business records.
- [ ] Boundary test confirms no AI/external/email/carrier calls.
- [ ] Boundary test confirms no business mutation.
- [ ] No DTO test updated.
- [ ] docs/analytics/* created.
- [ ] docs/workflow-map.md updated.
- [ ] docs/implementation-roadmap.md updated.
- [ ] docs/production-readiness.md updated.
- [ ] README.md updated.
- [ ] php artisan migrate:fresh --seed passed or blocker documented.
- [ ] php artisan supply:analytics-report supplier_performance --format=json passed or blocker documented.
- [ ] php artisan supply:analytics-report stockout_risk --format=json passed or blocker documented.
- [ ] php artisan supply:analytics-report logistics_performance --format=json passed or blocker documented.
- [ ] ./scripts/check-no-dto.sh passed.
- [ ] ./scripts/check-no-secrets.sh passed.
- [ ] ./scripts/check-project-docs.sh passed.
- [ ] php artisan test passed or blocker documented.
- [ ] Formatter passed if available.
- [ ] npm build passed if applicable.
- [ ] No secrets committed.
- [ ] No DTO created.
- [ ] No generated report exports committed.
- [ ] git status reviewed.
- [ ] Commit created.
- [ ] Push attempted.

## Required Commands

./scripts/check-no-dto.sh
./scripts/check-no-secrets.sh
./scripts/check-project-docs.sh
php artisan migrate:fresh --seed
php artisan supply:analytics-report supplier_performance --format=json
php artisan supply:analytics-report stockout_risk --format=json
php artisan supply:analytics-report logistics_performance --format=json
php artisan test

Optional:
./vendor/bin/pint
npm run build

## Commit Message

Add supply analytics and management reporting
